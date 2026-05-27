<?php

namespace MediaWiki\Extension\CategoryTransclude\Parser;

use MediaWiki\Config\Config;
use MediaWiki\Extension\CategoryTransclude\Repository\CategoryMemberRepository;
use MediaWiki\Extension\CategoryTransclude\Repository\DependencyTracker;
use MediaWiki\Extension\CategoryTransclude\Service\CategoryTitleResolver;
use MediaWiki\Extension\CategoryTransclude\Service\PermissionFilter;
use MediaWiki\Extension\CategoryTransclude\Service\TransclusionWikitextBuilder;
use MediaWiki\Html\Html;
use MediaWiki\Parser\Parser;
use PPFrame;

/**
 * 파서 함수의 핵심 렌더링 라이프사이클을 관리하고
 * 카테고리 조회, 필터링, Wikitext 생성 및 재귀 파싱을 실행하는 클래스입니다.
 */
class CategoryTranscludeRenderer {
	private ParamParser $paramParser;
	private CategoryTitleResolver $titleResolver;
	private CategoryMemberRepository $memberRepository;
	private PermissionFilter $permissionFilter;
	private TransclusionWikitextBuilder $wikitextBuilder;
	private DependencyTracker $dependencyTracker;
	private Config $config;

	/**
	 * @param ParamParser $paramParser
	 * @param CategoryTitleResolver $titleResolver
	 * @param CategoryMemberRepository $memberRepository
	 * @param PermissionFilter $permissionFilter
	 * @param TransclusionWikitextBuilder $wikitextBuilder
	 * @param DependencyTracker $dependencyTracker
	 * @param Config $config
	 */
	public function __construct(
		ParamParser $paramParser,
		CategoryTitleResolver $titleResolver,
		CategoryMemberRepository $memberRepository,
		PermissionFilter $permissionFilter,
		TransclusionWikitextBuilder $wikitextBuilder,
		DependencyTracker $dependencyTracker,
		Config $config
	) {
		$this->paramParser = $paramParser;
		$this->titleResolver = $titleResolver;
		$this->memberRepository = $memberRepository;
		$this->permissionFilter = $permissionFilter;
		$this->wikitextBuilder = $wikitextBuilder;
		$this->dependencyTracker = $dependencyTracker;
		$this->config = $config;
	}

	/**
	 * 파서 함수 입력을 렌더링하여 HTML 결과물로 반환합니다.
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 * @return array
	 */
	public function render( Parser $parser, PPFrame $frame, array $args ): array {
		// 1. 파라미터 파싱
		$params = $this->paramParser->parse( $frame, $args );

		// 2. 카테고리명 정규화 처리 (임시 저장해둔 emptyMessage 필드값 이용)
		$categoryInput = $params->emptyMessage;
		$defaultCategory = $this->config->get( 'CategoryTranscludeDefaultCategory' );
		$categoryTitle = $this->titleResolver->resolve( $categoryInput, $defaultCategory );

		if ( !$categoryTitle ) {
			// 카테고리 명이 유효하지 않은 경우 에러 반환
			$errorMsg = $parser->getFunctionLang()->msg(
				'categorytransclude-error-invalid-category',
				$categoryInput ?: $defaultCategory
			)->text();
			return [
				$params->showErrors ? Html::rawElement( 'span', [ 'class' => 'error' ], htmlspecialchars( $errorMsg ) ) : '',
				'noparse' => true,
				'isHTML' => true
			];
		}

		$params->categoryTitle = $categoryTitle;

		// 3. 현재 렌더링 중인 문서 정보 확인
		$currentTitle = $parser->getTitle();
		$currentPageId = $parser->getRevisionId() ? $currentTitle->getArticleID() : 0;
		$currentUser = $parser->getOptions()->getUserIdentity();

		// 4. 카테고리 멤버 조회
		$members = $this->memberRepository->findMembers( $params );

		// 5. 필터링 로직 수행 (자기 배제 및 권한 필터)
		$filteredMembers = [];
		foreach ( $members as $member ) {
			// exclude-self=1 설정 시, 현재 집계 페이지 자신은 목록에서 제외
			if ( $params->excludeSelf && $currentPageId > 0 && $member->pageId === $currentPageId ) {
				continue;
			}

			// 무한 재귀 호출 방지: 멤버 문서명이 현재 문서의 prefixedText와 같다면 제외
			if ( $member->prefixedText === $currentTitle->getPrefixedText() ) {
				continue;
			}

			// 현재 사용자가 읽기 권한이 없는 문서는 제외
			$memberTitle = $member->getTitle();
			if ( !$this->permissionFilter->canRead( $currentUser, $memberTitle ) ) {
				continue;
			}

			$filteredMembers[] = $member;
		}

		// 6. 의존성 기록 (Dependency Tracking)
		// 현재 페이지가 DB에 등록되어 존재하고(pageId > 0), 의존성 관리가 활성화되어 있을 때 기록
		if ( $currentPageId > 0 && $this->config->get( 'CategoryTranscludeEnableDependencyTracking' ) ) {
			$this->dependencyTracker->registerDependency(
				$currentPageId,
				$categoryTitle->getDBkey(),
				$params->getParamsHash()
			);
		}

		// 7. 빈 목록 처리
		if ( empty( $filteredMembers ) ) {
			// empty-message 인자가 지정되어 있지 않다면 다국어 기본 에러 메시지 렌더링
			$emptyText = $params->emptyMessage !== ''
				? $params->emptyMessage
				: $parser->getFunctionLang()->msg(
					'categorytransclude-error-empty',
					$categoryTitle->getPrefixedText()
				)->text();

			return [
				Html::rawElement( 'div', [ 'class' => 'categorytransclude-empty' ], htmlspecialchars( $emptyText ) ),
				'noparse' => true,
				'isHTML' => true
			];
		}

		// 8. Wikitext 생성
		$generatedWikitext = $this->wikitextBuilder->build( $filteredMembers, $params );

		// 9. 재귀적 파싱 수행 및 결과 반환
		$parsedHtml = $parser->recursiveTagParse( $generatedWikitext, $frame );

		return [
			$parsedHtml,
			'noparse' => true,
			'isHTML' => true
		];
	}
}
