<?php

/**
 * CategoryTransclude Extension의 의존성 재구성 유지보수 스크립트입니다.
 * 위키 내 모든 페이지를 순회하며 파서 함수를 사용하는 집계 페이지를 식별하고 의존성 정보를 갱신합니다.
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\Extension\CategoryTransclude\Repository\DependencyTracker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class RebuildCategoryTranscludeDeps extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'CategoryTransclude 카테고리 삽입 의존성 매핑 테이블을 처음부터 다시 빌드합니다.' );
	}

	/**
	 * 스크립트 실행 로직
	 */
	public function execute() {
		$services = MediaWikiServices::getInstance();
		$dbr = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$tracker = $services->getService( 'CategoryTransclude.DependencyTracker' );

		$this->output( "기존 의존성 테이블 데이터를 모두 비우는 중...\n" );
		$dbw = $services->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->newDeleteQueryBuilder()
			->deleteFrom( 'category_transclude_deps' )
			->where( [] )
			->caller( __METHOD__ )
			->execute();

		$this->output( "모든 위키 페이지 목록을 가져오는 중...\n" );
		$res = $dbr->newSelectQueryBuilder()
			->select( [ 'page_id', 'page_namespace', 'page_title' ] )
			->from( 'page' )
			->caller( __METHOD__ )
			->fetchResultSet();

		$count = 0;
		$parsedCount = 0;

		$parser = $services->getParserFactory()->create();
		$wikiPageFactory = $services->getWikiPageFactory();

		// 백그라운드 파싱에 필요한 기본 파서 옵션 구성
		$options = \ParserOptions::newFromSystem( $services->getMainConfig() );

		foreach ( $res as $row ) {
			$title = Title::makeTitle( $row->page_namespace, $row->page_title );
			if ( !$title ) {
				continue;
			}

			$wikiPage = $wikiPageFactory->newFromTitle( $title );
			if ( !$wikiPage ) {
				continue;
			}

			// 최신 revision 내용 조회
			$content = $wikiPage->getContent( \RevisionRecord::RAW );
			if ( !$content instanceof \WikitextContent ) {
				continue;
			}

			$wikitext = $content->getText();

			// 본문 텍스트 내에 파서 함수가 호출되는 패턴이 포함되어 있는지 확인
			if ( strpos( $wikitext, '#categorytransclude' ) !== false || strpos( $wikitext, '#분류삽입' ) !== false ) {
				$this->output( "집계 페이지 재파싱 및 의존성 갱신 중: " . $title->getPrefixedText() . "\n" );
				
				// 파싱 실행 (내부 CategoryTranscludeRenderer가 호출되면서 Dependency가 자동 기록됨)
				$parser->parse( $wikitext, $title, $options );
				$parsedCount++;
			}
			$count++;
		}

		$this->output( "완료! 총 {$count}개의 문서를 스캔하였고, {$parsedCount}개의 집계 페이지 의존성을 복구했습니다.\n" );
	}
}

$maintClass = RebuildCategoryTranscludeDeps::class;
require_once RUN_MAINTENANCE_IF_MAIN;
