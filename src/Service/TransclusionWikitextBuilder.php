<?php

namespace MediaWiki\Extension\CategoryTransclude\Service;

use MediaWiki\Extension\CategoryTransclude\Model\CategoryMember;
use MediaWiki\Extension\CategoryTransclude\Model\CategoryTranscludeParams;

/**
 * 카테고리 멤버 정보를 MediaWiki Wikitext 문법으로 조립하는 클래스입니다.
 */
class TransclusionWikitextBuilder {
	/**
	 * 카테고리 멤버 목록을 wikitext로 변환합니다.
	 *
	 * @param CategoryMember[] $members 필터링과 정렬이 완료된 카테고리 멤버 객체 배열
	 * @param CategoryTranscludeParams $params 파서 옵션 DTO
	 * @return string 조립 완료된 Wikitext 문자열
	 */
	public function build( array $members, CategoryTranscludeParams $params ): string {
		$wikitext = '';

		foreach ( $members as $member ) {
			if ( $member->namespace === NS_FILE ) {
				$wikitext .= $this->buildFileTransclusion( $member, $params );
			} else {
				$wikitext .= $this->buildPageTransclusion( $member, $params );
			}
		}

		return $wikitext;
	}

	/**
	 * 일반 페이지 멤버의 transclusion wikitext를 생성합니다.
	 *
	 * @param CategoryMember $member
	 * @param CategoryTranscludeParams $params
	 * @return string
	 */
	private function buildPageTransclusion( CategoryMember $member, CategoryTranscludeParams $params ): string {
		$titleText = $member->prefixedText;
		$out = '';

		// 1. Heading 생성
		if ( $params->headingLevel > 0 && $params->headingFormat !== 'none' ) {
			$equals = str_repeat( '=', $params->headingLevel );
			if ( $params->headingFormat === 'link' ) {
				$out .= "{$equals} [[:{$titleText}|{$titleText}]] {$equals}\n";
			} else {
				// plain 모드: 링크 없이 텍스트만 표시
				$out .= "{$equals} {$titleText} {$equals}\n";
			}
		}

		// 2. Transclusion wikitext 생성
		// Template namespace(10)는 {{Template:Title}} 또는 {{Title}} 형태로 가져옴
		// 그 외(Main namespace 등)는 Template과 구분하기 위해 반드시 앞에 콜론(:)을 붙여 {{:Title}} 형태로 작성
		if ( $member->namespace === NS_TEMPLATE ) {
			$out .= "{{{$titleText}}}\n\n";
		} else {
			$out .= "{{:{$titleText}}}\n\n";
		}

		return $out;
	}

	/**
	 * 파일 네임스페이스 멤버의 transclusion wikitext를 생성합니다.
	 *
	 * @param CategoryMember $member
	 * @param CategoryTranscludeParams $params
	 * @return string
	 */
	private function buildFileTransclusion( CategoryMember $member, CategoryTranscludeParams $params ): string {
		$titleText = $member->prefixedText;
		$out = '';

		// file-mode=link 인 경우 리스트 아이템 형태로 생성
		if ( $params->fileMode === 'link' ) {
			return "* [[:{$titleText}|{$titleText}]]\n";
		}

		// 1. Heading 생성
		if ( $params->headingLevel > 0 && $params->headingFormat !== 'none' ) {
			$equals = str_repeat( '=', $params->headingLevel );
			if ( $params->headingFormat === 'link' ) {
				$out .= "{$equals} [[:{$titleText}|{$titleText}]] {$equals}\n";
			} else {
				$out .= "{$equals} {$titleText} {$equals}\n";
			}
		}

		// 2. 파일 처리 분기
		if ( $params->fileMode === 'embed' ) {
			// 이미지 임베드 문법: [[File:Name.png|800px|File:Name.png]]
			$width = $params->imageWidth !== '' ? "|{$params->imageWidth}" : '';
			$out .= "[[{$titleText}{$width}|{$titleText}]]\n\n";
		} else {
			// description 모드: 파일 설명 문서 자체를 transclude
			$out .= "{{:{$titleText}}}\n\n";
		}

		return $out;
	}
}
