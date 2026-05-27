<?php

use MediaWiki\Extension\CategoryTransclude\Hooks\InvalidationHooks;
use MediaWiki\Extension\CategoryTransclude\Hooks\ParserHooks;
use MediaWiki\Extension\CategoryTransclude\Parser\CategoryTranscludeRenderer;
use MediaWiki\Extension\CategoryTransclude\Parser\ParamParser;
use MediaWiki\Extension\CategoryTransclude\Repository\CategoryMemberRepository;
use MediaWiki\Extension\CategoryTransclude\Repository\DependencyTracker;
use MediaWiki\Extension\CategoryTransclude\Service\CategoryTitleResolver;
use MediaWiki\Extension\CategoryTransclude\Service\NamespaceResolver;
use MediaWiki\Extension\CategoryTransclude\Service\PermissionFilter;
use MediaWiki\Extension\CategoryTransclude\Service\TransclusionWikitextBuilder;
use MediaWiki\MediaWikiServices;

/**
 * CategoryTransclude Extension의 의존성 주입(Dependency Injection) 설정을 담은 파일입니다.
 * 미디어위키의 글로벌 서비스 컨테이너에 Extension 서비스들을 등록합니다.
 */
return [
	'CategoryTransclude.ParamParser' => static function ( MediaWikiServices $services ) {
		return new ParamParser(
			$services->getMainConfig(),
			new NamespaceResolver( $services->getNamespaceInfo() )
		);
	},

	'CategoryTransclude.CategoryMemberRepository' => static function ( MediaWikiServices $services ) {
		return new CategoryMemberRepository(
			$services->getDBLoadBalancer(),
			$services->getTitleFactory()
		);
	},

	'CategoryTransclude.DependencyTracker' => static function ( MediaWikiServices $services ) {
		return new DependencyTracker(
			$services->getDBLoadBalancer()
		);
	},

	'CategoryTransclude.Renderer' => static function ( MediaWikiServices $services ) {
		return new CategoryTranscludeRenderer(
			$services->getService( 'CategoryTransclude.ParamParser' ),
			new CategoryTitleResolver( $services->getTitleFactory() ),
			$services->getService( 'CategoryTransclude.CategoryMemberRepository' ),
			new PermissionFilter( $services->getPermissionManager() ),
			new TransclusionWikitextBuilder(),
			$services->getService( 'CategoryTransclude.DependencyTracker' ),
			$services->getMainConfig()
		);
	},

	// extension.json의 HookHandlers 설정에 대응되는 훅 핸들러 서비스
	'CategoryTranscludeParserHooks' => static function ( MediaWikiServices $services ) {
		return new ParserHooks(
			$services->getService( 'CategoryTransclude.Renderer' )
		);
	},

	'CategoryTranscludeInvalidationHooks' => static function ( MediaWikiServices $services ) {
		return new InvalidationHooks(
			$services->getService( 'CategoryTransclude.DependencyTracker' ),
			$services->getTitleFactory(),
			$services->getMainConfig()
		);
	}
];
