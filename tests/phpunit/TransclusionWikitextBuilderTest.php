<?php

namespace MediaWiki\Extension\CategoryTransclude\Tests;

use MediaWiki\Extension\CategoryTransclude\Model\CategoryMember;
use MediaWiki\Extension\CategoryTransclude\Model\CategoryTranscludeParams;
use MediaWiki\Extension\CategoryTransclude\Service\TransclusionWikitextBuilder;
use MediaWiki\Title\TitleValue;
use PHPUnit\Framework\TestCase;

/**
 * TransclusionWikitextBuilder 클래스의 단위 테스트입니다.
 */
class TransclusionWikitextBuilderTest extends TestCase {
	private TransclusionWikitextBuilder $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new TransclusionWikitextBuilder();
	}

	/**
	 * 일반 페이지(Main Namespace)를 대상으로 하는 transclusion wikitext 조립 결과를 검증합니다.
	 */
	public function testBuildPageTransclusion() {
		$member = new CategoryMember();
		$member->pageId = 42;
		$member->namespace = 0; // NS_MAIN
		$member->dbKey = 'Foo_Bar';
		$member->prefixedText = 'Foo Bar';

		$params = new CategoryTranscludeParams();
		$params->headingLevel = 2;
		$params->headingFormat = 'link';
		$params->fileMode = 'description';

		$result = $this->builder->build( [ $member ], $params );

		$expected = "== [[:Foo Bar|Foo Bar]] ==\n{{:Foo Bar}}\n\n";
		$this->assertEquals( $expected, $result, '링크가 포함된 H2 헤더와 본문 transclude 구문이 올바르게 생성되어야 합니다.' );
	}

	/**
	 * H0(헤더 비활성화) 옵션으로 페이지 transclusion wikitext를 생성하는 결과를 검증합니다.
	 */
	public function testBuildPageTransclusionWithoutHeading() {
		$member = new CategoryMember();
		$member->pageId = 42;
		$member->namespace = 0; // NS_MAIN
		$member->prefixedText = 'Foo Bar';

		$params = new CategoryTranscludeParams();
		$params->headingLevel = 0; // 헤더 레벨 0 (출력하지 않음)
		$params->headingFormat = 'link';

		$result = $this->builder->build( [ $member ], $params );

		$expected = "{{:Foo Bar}}\n\n";
		$this->assertEquals( $expected, $result, '헤더 옵션이 비활성화되었을 때는 transclude 문법만 생성되어야 합니다.' );
	}

	/**
	 * 템플릿 네임스페이스 문서의 경우 transclude 문법에 콜론이 생략되는지 검증합니다.
	 */
	public function testBuildTemplateTransclusion() {
		$member = new CategoryMember();
		$member->pageId = 100;
		$member->namespace = 10; // NS_TEMPLATE
		$member->prefixedText = 'Template:InfoBox';

		$params = new CategoryTranscludeParams();
		$params->headingLevel = 0;

		$result = $this->builder->build( [ $member ], $params );

		// 템플릿의 경우 {{Template:InfoBox}} 형태로 출력 (콜론 없음)
		$expected = "{{Template:InfoBox}}\n\n";
		$this->assertEquals( $expected, $result, '템플릿 네임스페이스 문서는 transclude 콜론(:) 없이 조립되어야 합니다.' );
	}

	/**
	 * 파일 네임스페이스의 이미지 임베드 모드(file-mode=embed) 렌더링 결과를 검증합니다.
	 */
	public function testBuildFileEmbedTransclusion() {
		$member = new CategoryMember();
		$member->pageId = 55;
		$member->namespace = 6; // NS_FILE
		$member->prefixedText = 'File:Diagram.png';

		$params = new CategoryTranscludeParams();
		$params->headingLevel = 3;
		$params->headingFormat = 'plain';
		$params->fileMode = 'embed';
		$params->imageWidth = '600px';

		$result = $this->builder->build( [ $member ], $params );

		$expected = "=== File:Diagram.png ===\n[[File:Diagram.png|600px|File:Diagram.png]]\n\n";
		$this->assertEquals( $expected, $result, 'H3 플레인 텍스트 헤더와 이미지 임베드 wikitext 문법이 매칭되어야 합니다.' );
	}

	/**
	 * 파일 네임스페이스의 링크 모드(file-mode=link) 렌더링 결과를 검증합니다.
	 */
	public function testBuildFileLinkTransclusion() {
		$member = new CategoryMember();
		$member->pageId = 55;
		$member->namespace = 6; // NS_FILE
		$member->prefixedText = 'File:Diagram.png';

		$params = new CategoryTranscludeParams();
		$params->headingLevel = 2;
		$params->fileMode = 'link';

		$result = $this->builder->build( [ $member ], $params );

		$expected = "* [[:File:Diagram.png|File:Diagram.png]]\n";
		$this->assertEquals( $expected, $result, 'link 모드에서는 헤더 없이 목록형(Bullet) 파일 링크 구문만 반환해야 합니다.' );
	}
}
