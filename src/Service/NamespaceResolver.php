<?php

namespace MediaWiki\Extension\CategoryTransclude\Service;

use MediaWiki\Title\NamespaceInfo;

/**
 * 사용자 입력 문자열(네임스페이스 숫자 ID 혹은 텍스트 명칭)을 정수 ID 배열로 변환하는 헬퍼 클래스입니다.
 */
class NamespaceResolver {
	private NamespaceInfo $namespaceInfo;

	/**
	 * @param NamespaceInfo $namespaceInfo
	 */
	public function __construct( NamespaceInfo $namespaceInfo ) {
		$this->namespaceInfo = $namespaceInfo;
	}

	/**
	 * 입력값을 해석하여 네임스페이스 ID 배열로 반환합니다.
	 *
	 * @param string|int|array $input '0,File,Help' 형태의 문자열 또는 배열/숫자 입력
	 * @return int[]
	 */
	public function resolve( $input ): array {
		if ( $input === null || $input === '' ) {
			return [];
		}

		if ( is_array( $input ) ) {
			$rawItems = $input;
		} else {
			$rawItems = explode( ',', (string)$input );
		}

		$resolved = [];
		foreach ( $rawItems as $item ) {
			$trimmed = trim( $item );
			if ( $trimmed === '' ) {
				continue;
			}

			// 숫자인 경우 바로 추가
			if ( is_numeric( $trimmed ) ) {
				$nsId = (int)$trimmed;
				if ( $this->namespaceInfo->exists( $nsId ) ) {
					$resolved[] = $nsId;
				}
				continue;
			}

			// 문자열 이름인 경우 Canonical ID 조회
			// 대소문자를 구분하지 않고 공백을 _로 변환하여 검색 시도
			$canonicalName = str_replace( ' ', '_', $trimmed );
			$nsId = $this->namespaceInfo->getCanonicalIndex( strtolower( $canonicalName ) );

			if ( $nsId !== null ) {
				$resolved[] = $nsId;
			} else {
				// Canonical Index로 조회가 안 될 경우, 
				// 코어의 기본 및 사용자 정의 네임스페이스 전체 목록을 조회하여 로컬 이름 매칭
				$namespaces = $this->namespaceInfo->getCanonicalNamespaces();
				foreach ( $namespaces as $id => $name ) {
					if ( strcasecmp( $name, $canonicalName ) === 0 ) {
						$resolved[] = $id;
						continue 2;
					}
				}
			}
		}

		return array_unique( $resolved );
	}
}
