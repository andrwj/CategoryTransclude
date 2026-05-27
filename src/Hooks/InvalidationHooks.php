<?php

namespace MediaWiki\Extension\CategoryTransclude\Hooks;

use MediaWiki\Category\Category;
use MediaWiki\Page\Hook\CategoryAfterPageAddedHook;
use MediaWiki\Page\Hook\CategoryAfterPageRemovedHook;
use MediaWiki\Config\Config;
use MediaWiki\Extension\CategoryTransclude\Job\PurgeAggregatePageJob;
use MediaWiki\Extension\CategoryTransclude\Repository\DependencyTracker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Page\WikiPage;

/**
 * 카테고리 멤버가 추가되거나 삭제되었을 때의 훅을 처리하여
 * 관련 집계 페이지의 캐시 무효화 작업을 실행 큐에 대기시키는 클래스입니다.
 */
class InvalidationHooks implements CategoryAfterPageAddedHook, CategoryAfterPageRemovedHook {
	private DependencyTracker $dependencyTracker;
	private TitleFactory $titleFactory;
	private Config $config;

	/**
	 * @param DependencyTracker $dependencyTracker
	 * @param TitleFactory $titleFactory
	 * @param Config $config
	 */
	public function __construct(
		DependencyTracker $dependencyTracker,
		TitleFactory $titleFactory,
		Config $config
	) {
		$this->dependencyTracker = $dependencyTracker;
		$this->titleFactory = $titleFactory;
		$this->config = $config;
	}

	/**
	 * 카테고리에 멤버 문서가 추가되었을 때 실행됩니다.
	 *
	 * @param Category $category
	 * @param WikiPage $wikiPage
	 */
	public function onCategoryAfterPageAdded( $category, $wikiPage ): void {
		$this->triggerPurge( $category );
	}

	/**
	 * 카테고리에서 멤버 문서가 제거되었을 때 실행됩니다.
	 *
	 * @param Category $category
	 * @param WikiPage $wikiPage
	 * @param int $id Page ID (original ID in case of page deletions)
	 */
	public function onCategoryAfterPageRemoved( $category, $wikiPage, $id ): void {
		$this->triggerPurge( $category );
	}

	/**
	 * 카테고리에 의존하는 집계 페이지들을 찾아 캐시 갱신 작업을 큐에 밀어넣습니다.
	 *
	 * @param Category $category 변동이 발생한 카테고리 객체
	 */
	private function triggerPurge( Category $category ): void {
		// 의존성 추적이 활성화되어 있지 않다면 생략
		if ( !$this->config->get( 'CategoryTranscludeEnableDependencyTracking' ) ) {
			return;
		}

		$categoryTitle = $category->getTitle();
		if ( !$categoryTitle ) {
			return;
		}

		$categoryDbKey = $categoryTitle->getDBkey();

		// 이 카테고리에 의존하는 집계 페이지 page_id 목록 조회
		$pageIds = $this->dependencyTracker->getDependentPages( $categoryDbKey );
		if ( empty( $pageIds ) ) {
			return;
		}

		// 백그라운드 작업을 위한 Job 객체들 조립
		$jobs = [];
		foreach ( $pageIds as $pageId ) {
			// 작업의 대상이 되는 카테고리 타이틀을 메인 파라미터 형태로 지정
			$jobs[] = new PurgeAggregatePageJob( $categoryTitle, [ 'pageId' => $pageId ] );
		}

		// 미디어위키 JobQueue에 Job 추가
		if ( !empty( $jobs ) ) {
			$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroup();
			$jobQueueGroup->push( $jobs );
		}
	}
}
