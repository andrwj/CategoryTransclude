<?php

namespace MediaWiki\Extension\CategoryTransclude\Service;

use MediaWiki\Title\TitleFactory;
use MediaWiki\Title\Title;

/**
 * 사용자 입력 문자열을 정규화된 Category Title 객체로 해석하는 클래스입니다.
 */
class CategoryTitleResolver {
	private TitleFactory $titleFactory;

	/**
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	/**
	 * 입력값을 해석하여 카테고리 Title 객체를 반환합니다.
	 *
	 * @param string $input 사용자 입력 카테고리 명칭 (예: 'KontexusNote', 'Category:KontexusNote')
	 * @param string $defaultCategory 기본 카테고리 명칭
	 * @return Title|null 잘못된 명칭일 경우 null 반환
	 */
	public function resolve( string $input, string $defaultCategory = '' ): ?Title {
		$name = trim( $input );
		if ( $name === '' ) {
			$name = trim( $defaultCategory );
		}

		if ( $name === '' ) {
			return null;
		}

		// 앞쪽의 콜론(:) 제거
		if ( strpos( $name, ':' ) === 0 ) {
			$name = substr( $name, 1 );
		}

		// 먼저 그대로 파싱해 봄
		$title = $this->titleFactory->newFromText( $name );

		// 파싱 결과가 올바른 카테고리 네임스페이스(NS_CATEGORY = 14)가 아니라면,
		// 카테고리 접두사를 강제로 붙여서 다시 시도
		if ( !$title || $title->getNamespace() !== NS_CATEGORY ) {
			// 'Category:' 접두사 결합
			$title = $this->titleFactory->newFromText( 'Category:' . $name );
		}

		if ( !$title || $title->getNamespace() !== NS_CATEGORY ) {
			return null;
		}

		return $title;
	}
}
