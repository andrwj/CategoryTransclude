<?php

namespace MediaWiki\Extension\CategoryTransclude\Tests;

use HashConfig;
use MediaWiki\Extension\CategoryTransclude\Parser\ParamParser;
use MediaWiki\Extension\CategoryTransclude\Service\NamespaceResolver;
use MediaWiki\Title\TitleValue;
use PHPUnit\Framework\TestCase;
use PPFrame;

/**
 * ParamParser 클래스의 단위 테스트입니다.
 */
class ParamParserTest extends TestCase {
	/**
	 * @var HashConfig
	 */
	private $config;

	/**
	 * @var NamespaceResolver|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $namespaceResolverMock;

	protected function setUp(): void {
		parent::setUp();
		$this->config = new HashConfig( [
			'CategoryTranscludeDefaultCategory' => 'Category:KontexusNote',
			'CategoryTranscludeDefaultLimit' => 200,
			'CategoryTranscludeMaxLimit' => 500,
			'CategoryTranscludeDefaultHeadingLevel' => 2,
		] );

		$this->namespaceResolverMock = $this->createMock( NamespaceResolver::class );
	}

	/**
	 * @param array $map
	 * @return PPFrame|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function createFrameMock( array $map ) {
		$frame = $this->createMock( PPFrame::class );
		$frame->method( 'expand' )
			->willReturnCallback( function ( $arg ) use ( $map ) {
				return $map[$arg] ?? $arg;
			} );
		return $frame;
	}

	public function testBasicParsing() {
		// PPFrame이 입력 인자들을 문자열로 치환하는 동작을 목킹
		$args = [ 'arg0', 'arg1', 'arg2' ];
		$frameMap = [
			'arg0' => 'TestCategory',
			'arg1' => 'limit=100',
			'arg2' => 'heading=3',
		];
		$frame = $this->createFrameMock( $frameMap );

		$parser = new ParamParser( $this->config, $this->namespaceResolverMock );
		$params = $parser->parse( $frame, $args );

		// 파싱 결과 필드들 검증
		$this->assertEquals( 'TestCategory', $params->emptyMessage, '첫 번째 positional 인자가 카테고리명 임시 보관 필드에 들어가야 합니다.' );
		$this->assertEquals( 100, $params->limit, 'limit가 100으로 해석되어야 합니다.' );
		$this->assertEquals( 3, $params->headingLevel, 'heading 레벨이 3으로 해석되어야 합니다.' );
		$this->assertEquals( 'link', $params->headingFormat, '기본 heading-format은 link여야 합니다.' );
		$this->assertTrue( $params->excludeSelf, 'exclude-self의 기본값은 true(1)여야 합니다.' );
		$this->assertEquals( 'keep', $params->redirectMode, 'redirect 기본값은 keep이어야 합니다.' );
		$this->assertEquals( 'description', $params->fileMode, 'fileMode 기본값은 description이어야 합니다.' );
	}

	public function testLimitClamping() {
		$args = [ 'arg0', 'arg1' ];
		$frameMap = [
			'arg0' => 'TestCategory',
			'arg1' => 'limit=9999', // 최대 한도 500을 넘는 입력값
		];
		$frame = $this->createFrameMock( $frameMap );

		$parser = new ParamParser( $this->config, $this->namespaceResolverMock );
		$params = $parser->parse( $frame, $args );

		$this->assertEquals( 500, $params->limit, '설정된 MaxLimit(500)을 초과하는 limit 입력은 500으로 제한(clamp)되어야 합니다.' );
	}

	public function testNamespaceResolution() {
		$args = [ 'arg0', 'arg1' ];
		$frameMap = [
			'arg0' => 'TestCategory',
			'arg1' => 'namespace=0,File',
		];
		$frame = $this->createFrameMock( $frameMap );

		// NamespaceResolver가 [0, 6] 정수 네임스페이스 ID 배열을 반환한다고 목킹
		$this->namespaceResolverMock->expects( $this->once() )
			->method( 'resolve' )
			->with( '0,File' )
			->willReturn( [ 0, 6 ] );

		$parser = new ParamParser( $this->config, $this->namespaceResolverMock );
		$params = $parser->parse( $frame, $args );

		$this->assertEquals( [ 0, 6 ], $params->namespaces, '네임스페이스 문자열 옵션이 해석기(Resolver)를 거쳐 DTO에 정상 바인딩되어야 합니다.' );
	}
}
