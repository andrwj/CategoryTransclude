<?php

namespace MediaWiki\Extension\CategoryTransclude\Job;

use Job;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

/**
 * 카테고리 링크가 변동되었을 때 해당 카테고리를 참조하는 집계 페이지들의 캐시를
 * 백그라운드 JobQueue 태스크로 퍼지(Purge) 처리하는 클래스입니다.
 */
class PurgeAggregatePageJob extends Job {
	/**
	 * @param Title $title Job의 주 타이틀 (임시 타이틀을 지정할 수 있습니다)
	 * @param array $params Job 파라미터 (pageId가 필수로 들어갑니다)
	 */
	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'categoryTranscludePurgeAggregate', $title, $params );
	}

	/**
	 * Job 실행 로직
	 *
	 * @return bool 성공 여부
	 */
	public function run(): bool {
		$pageId = $this->params['pageId'] ?? null;
		if ( !$pageId ) {
			return true;
		}

		$services = MediaWikiServices::getInstance();
		$title = $services->getTitleFactory()->newFromID( $pageId );
		if ( !$title ) {
			return true;
		}

		// 위키 페이지 객체를 획득하여 캐시 퍼지 수행
		$wikiPageFactory = $services->getWikiPageFactory();
		$wikiPage = $wikiPageFactory->newFromTitle( $title );

		if ( $wikiPage ) {
			// doPurge()는 파서 캐시를 포함해 CDN 캐시 등 해당 페이지의 출력을 만료시킵니다.
			$wikiPage->doPurge();
		}

		return true;
	}
}
