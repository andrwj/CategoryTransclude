<?php

namespace MediaWiki\Extension\CategoryTransclude\Hooks;

use MediaWiki\Extension\CategoryTransclude\Parser\CategoryTranscludeRenderer;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Parser;
use PPFrame;

/**
 * 미디어위키 ParserFirstCallInit 훅을 받아 파서 함수를 등록하는 클래스입니다.
 */
class ParserHooks implements ParserFirstCallInitHook {
	private CategoryTranscludeRenderer $renderer;

	/**
	 * @param CategoryTranscludeRenderer $renderer
	 */
	public function __construct( CategoryTranscludeRenderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * ParserFirstCallInit 훅 발생 시 호출됩니다.
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ): void {
		$parser->setFunctionHook(
			'categorytransclude',
			[ $this, 'renderCategoryTransclude' ],
			Parser::SFH_OBJECT_ARGS
		);
	}

	/**
	 * {{#categorytransclude:...}} 또는 {{#분류삽입:...}} 파서 함수가 호출될 때 실행되는 콜백 함수입니다.
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 * @return array|string
	 */
	public function renderCategoryTransclude( Parser $parser, PPFrame $frame, array $args ) {
		return $this->renderer->render( $parser, $frame, $args );
	}
}
