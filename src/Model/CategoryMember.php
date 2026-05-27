<?php

namespace MediaWiki\Extension\CategoryTransclude\Model;

use MediaWiki\Title\Title;

/**
 * 카테고리 멤버 조회 결과를 구조화하여 저장하는 모델 클래스입니다.
 */
class CategoryMember {
	/** @var int 문서 ID (page_id) */
	public int $pageId;

	/** @var int 문서의 네임스페이스 ID (page_namespace) */
	public int $namespace;

	/** @var string 문서의 DB 키 (page_title) */
	public string $dbKey;

	/** @var string 접두사를 포함한 형식화된 문서 명칭 */
	public string $prefixedText;

	/** @var string 카테고리 내의 정렬 키 (cl_sortkey) */
	public string $sortKey;

	/** @var string 멤버의 카테고리 링크 내 타입 ('page', 'file', 'subcat') */
	public string $type;

	/** @var string|null 카테고리 가입 시각 타임스탬프 (cl_timestamp) */
	public ?string $timestamp;

	/** @var bool 리다이렉트 페이지 여부 */
	public bool $isRedirect;

	/**
	 * 미디어위키 Title 객체를 획득합니다.
	 *
	 * @return Title
	 */
	public function getTitle(): Title {
		return Title::makeTitle( $this->namespace, $this->dbKey );
	}
}
