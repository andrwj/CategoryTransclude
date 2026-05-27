<?php

namespace MediaWiki\Extension\CategoryTransclude\Repository;

use Wikimedia\Rdbms\ILoadBalancer;

/**
 * category_transclude_deps 테이블에 의존성을 기록하고 조회하는 리포지토리 클래스입니다.
 */
class DependencyTracker {
	private ILoadBalancer $loadBalancer;

	/**
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * 특정 집계 페이지와 카테고리 파라미터 간의 의존성을 기록(Upsert)합니다.
	 *
	 * @param int $pageId 집계 대상 페이지 ID
	 * @param string $categoryDbKey 카테고리 DB Key
	 * @param string $paramsHash 파라미터 DTO 해시
	 */
	public function registerDependency( int $pageId, string $categoryDbKey, string $paramsHash ): void {
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );

		$dbw->replace(
			'category_transclude_deps',
			[ [ 'ctd_page_id', 'ctd_category', 'ctd_params_hash' ] ],
			[
				'ctd_page_id' => $pageId,
				'ctd_category' => $categoryDbKey,
				'ctd_params_hash' => $paramsHash,
				'ctd_touched' => $dbw->timestamp()
			],
			__METHOD__
		);
	}

	/**
	 * 특정 카테고리에 의존성을 가지고 있는 고유한 집계 페이지 ID 목록을 조회합니다.
	 *
	 * @param string $categoryDbKey 카테고리 DB Key
	 * @return int[]
	 */
	public function getDependentPages( string $categoryDbKey ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$res = $dbr->newSelectQueryBuilder()
			->select( 'ctd_page_id' )
			->distinct()
			->from( 'category_transclude_deps' )
			->where( [ 'ctd_category' => $categoryDbKey ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$pageIds = [];
		foreach ( $res as $row ) {
			$pageIds[] = (int)$row->ctd_page_id;
		}

		return $pageIds;
	}

	/**
	 * 특정 집계 페이지의 기존 의존성 기록들을 모두 삭제합니다.
	 *
	 * @param int $pageId
	 */
	public function deletePageDependencies( int $pageId ): void {
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );

		$dbw->newDeleteQueryBuilder()
			->deleteFrom( 'category_transclude_deps' )
			->where( [ 'ctd_page_id' => $pageId ] )
			->caller( __METHOD__ )
			->execute();
	}
}
