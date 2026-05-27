<?php

namespace MediaWiki\Extension\CategoryTransclude\Hooks;

use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\Installer\DatabaseUpdater;

/**
 * DB 스키마 업데이트를 등록하는 훅 핸들러입니다.
 *
 * LoadExtensionSchemaUpdates 훅을 통해 maintenance/update.php 실행 시
 * category_transclude_deps 테이블이 자동으로 생성됩니다.
 */
class SchemaHooks implements LoadExtensionSchemaUpdatesHook {

	/**
	 * LoadExtensionSchemaUpdates 훅 핸들러.
	 *
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$dbType = $updater->getDB()->getType();

		// DB 종류에 따라 적절한 SQL 파일 경로 선택
		$sqlDir = dirname( __DIR__, 2 ) . '/sql';
		if ( $dbType === 'postgres' ) {
			$sqlFile = "$sqlDir/postgres/category_transclude_deps.sql";
		} elseif ( $dbType === 'sqlite' ) {
			$sqlFile = "$sqlDir/sqlite/category_transclude_deps.sql";
		} else {
			// MySQL / MariaDB (기본값)
			$sqlFile = "$sqlDir/mysql/category_transclude_deps.sql";
		}

		// 테이블이 없으면 생성합니다.
		$updater->addExtensionTable( 'category_transclude_deps', $sqlFile );
	}
}
