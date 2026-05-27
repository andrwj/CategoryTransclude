<?php

namespace MediaWiki\Extension\CategoryTransclude\Parser;

use MediaWiki\Config\Config;
use MediaWiki\Extension\CategoryTransclude\Model\CategoryTranscludeParams;
use MediaWiki\Extension\CategoryTransclude\Service\NamespaceResolver;
use PPFrame;

/**
 * 파서 함수의 인자 배열을 파싱하고 유효성 검사를 거쳐 Params DTO 객체로 조립하는 클래스입니다.
 */
class ParamParser {
	private Config $config;
	private NamespaceResolver $namespaceResolver;

	/**
	 * @param Config $config
	 * @param NamespaceResolver $namespaceResolver
	 */
	public function __construct( Config $config, NamespaceResolver $namespaceResolver ) {
		$this->config = $config;
		$this->namespaceResolver = $namespaceResolver;
	}

	/**
	 * 파서 인자들을 해석하여 CategoryTranscludeParams 객체를 빌드합니다.
	 *
	 * @param PPFrame $frame 파서 프레임 객체
	 * @param array $args 파서 함수 인자 배열
	 * @return CategoryTranscludeParams
	 */
	public function parse( PPFrame $frame, array $args ): CategoryTranscludeParams {
		$positional = [];
		$named = [];

		// 1. 인자 확장 및 Positional / Named 파라미터 분류
		foreach ( $args as $arg ) {
			$expanded = trim( $frame->expand( $arg ) );
			if ( strpos( $expanded, '=' ) !== false ) {
				[ $key, $value ] = array_map( 'trim', explode( '=', $expanded, 2 ) );
				$named[strtolower( $key )] = $value;
			} else {
				$positional[] = $expanded;
			}
		}

		$params = new CategoryTranscludeParams();

		// 2. 대상 카테고리 명칭 획득
		// 우선순위: 1. 첫 번째 positional 인자, 2. category=... named 인자, 3. global default 설정
		$categoryInput = '';
		if ( isset( $positional[0] ) && $positional[0] !== '' ) {
			$categoryInput = $positional[0];
		} elseif ( isset( $named['category'] ) && $named['category'] !== '' ) {
			$categoryInput = $named['category'];
		}
		// 실제 Title 맵핑은 TitleResolver에서 처리하므로 여기선 임시 저장 문자열로 남겨둠
		$params->emptyMessage = $categoryInput; // 임시로 카테고리 입력 저장 (이후 Title로 변환되면 갱신됨)

		// 3. 멤버 타입 (types) 파싱
		$typeInput = $named['type'] ?? 'page';
		$types = array_map( 'trim', explode( ',', strtolower( $typeInput ) ) );
		// 'all'이 포함되어 있으면 모든 유효타입으로 맵핑
		if ( in_array( 'all', $types, true ) ) {
			$params->types = [ 'page', 'file', 'subcat' ];
		} else {
			$params->types = array_intersect( $types, [ 'page', 'file', 'subcat' ] );
			if ( empty( $params->types ) ) {
				$params->types = [ 'page' ]; // 기본값
			}
		}

		// 4. 네임스페이스 필터 해석
		$params->namespaces = isset( $named['namespace'] )
			? $this->namespaceResolver->resolve( $named['namespace'] )
			: null;

		$params->excludeNamespaces = isset( $named['exclude-namespace'] )
			? $this->namespaceResolver->resolve( $named['exclude-namespace'] )
			: null;

		// 5. 정렬 기준 및 방향
		$orderInput = strtolower( $named['order'] ?? 'sortkey' );
		$params->order = in_array( $orderInput, [ 'sortkey', 'title', 'timestamp', 'pageid' ], true )
			? $orderInput
			: 'sortkey';

		$dirInput = strtolower( $named['dir'] ?? 'asc' );
		$params->direction = in_array( $dirInput, [ 'asc', 'desc' ], true ) ? $dirInput : 'asc';

		// 6. limit 값 검증 및 Clamping
		$defaultLimit = (int)$this->config->get( 'CategoryTranscludeDefaultLimit' );
		$maxLimit = (int)$this->config->get( 'CategoryTranscludeMaxLimit' );
		$limitInput = isset( $named['limit'] ) ? (int)$named['limit'] : $defaultLimit;

		if ( $limitInput <= 0 ) {
			$limitInput = $defaultLimit;
		}

		if ( $limitInput > $maxLimit ) {
			$limitInput = $maxLimit;
		}
		$params->limit = $limitInput;

		// 7. Heading 설정
		$defaultHeading = (int)$this->config->get( 'CategoryTranscludeDefaultHeadingLevel' );
		$headingInput = isset( $named['heading'] ) ? (int)$named['heading'] : $defaultHeading;
		$params->headingLevel = ( $headingInput >= 0 && $headingInput <= 6 ) ? $headingInput : $defaultHeading;

		$headingFormatInput = strtolower( $named['heading-format'] ?? 'link' );
		$params->headingFormat = in_array( $headingFormatInput, [ 'link', 'plain', 'none' ], true )
			? $headingFormatInput
			: 'link';

		// 8. 자기 배제 설정
		$excludeSelfInput = $named['exclude-self'] ?? '1';
		$params->excludeSelf = ( $excludeSelfInput === '1' || strtolower( $excludeSelfInput ) === 'true' );

		// 9. 리다이렉트 처리 방식
		$redirectInput = strtolower( $named['redirect'] ?? 'keep' );
		$params->redirectMode = in_array( $redirectInput, [ 'keep', 'skip', 'follow' ], true )
			? $redirectInput
			: 'keep';

		// 10. 파일 렌더링 옵션
		$fileModeInput = strtolower( $named['file-mode'] ?? 'description' );
		$params->fileMode = in_array( $fileModeInput, [ 'description', 'embed', 'link' ], true )
			? $fileModeInput
			: 'description';

		$params->imageWidth = $named['image-width'] ?? '800px';

		// 11. 메시지 및 기타 제어 플래그
		$params->emptyMessage = $named['empty-message'] ?? '';
		$params->showErrors = ( ( $named['show-errors'] ?? '0' ) === '1' );
		$params->debug = ( ( $named['debug'] ?? '0' ) === '1' );

		return $params;
	}
}
