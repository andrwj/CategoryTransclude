<?php

namespace MediaWiki\Extension\CategoryTransclude\Model;

use MediaWiki\Title\Title;

/**
 * #categorytransclude 파서 함수의 매개변수 설정을 관리하는 DTO 클래스입니다.
 */
class CategoryTranscludeParams {
	/** @var Title 대상 카테고리 Title 객체 */
	public Title $categoryTitle;

	/** @var string[] 조회할 카테고리 멤버 타입 목록 (예: ['page', 'file']) */
	public array $types;

	/** @var int[]|null 포함할 네임스페이스 ID 목록 (null이면 제한 없음) */
	public ?array $namespaces = null;

	/** @var int[]|null 제외할 네임스페이스 ID 목록 (null이면 없음) */
	public ?array $excludeNamespaces = null;

	/** @var string 정렬 기준 ('sortkey', 'title', 'timestamp', 'pageid') */
	public string $order;

	/** @var string 정렬 방향 ('asc', 'desc') */
	public string $direction;

	/** @var int 최대 멤버 조회 개수 */
	public int $limit;

	/** @var int 각 멤버 제목의 Heading 레벨 (0이면 제목 없음) */
	public int $headingLevel;

	/** @var string Heading 출력 방식 ('link', 'plain', 'none') */
	public string $headingFormat;

	/** @var bool 집계 페이지 자체를 목록에서 제외할지 여부 */
	public bool $excludeSelf;

	/** @var string 리다이렉트 문서 처리 방식 ('keep', 'skip', 'follow') */
	public string $redirectMode;

	/** @var string 파일 멤버 렌더링 방식 ('description', 'embed', 'link') */
	public string $fileMode;

	/** @var string 파일 임베드 시 이미지 크기 지정값 (예: '800px') */
	public string $imageWidth;

	/** @var string 결과가 없을 때 표시할 빈 목록 메시지 (기본값은 i18n 메시지) */
	public string $emptyMessage;

	/** @var bool 사용자에게 상세 오류 정보를 노출할지 여부 */
	public bool $showErrors;

	/** @var bool 디버그 정보 출력 여부 */
	public bool $debug;

	/**
	 * 내용 없이 제목만 표시할 페이지 패턴 목록.
	 * 각 항목은 '%' 와일드카드를 지원하는 glob 패턴이며, ';'으로 구분하여 입력받습니다.
	 * Namespace가 없으면 Main(NS=0)으로 간주합니다.
	 *
	 * @var string[]
	 */
	public array $titleOnlyPatterns = [];

	/**
	 * 파라미터 조합의 고유 해시값을 계산합니다.
	 * 캐시 및 의존성 관리 테이블에서 옵션 변경 여부를 감지할 때 사용합니다.
	 *
	 * @return string 64글자 바이너리/텍스트 호환 SHA-256 해시값
	 */
	public function getParamsHash(): string {
		$data = [
			'category' => $this->categoryTitle->getDBkey(),
			'types' => $this->types,
			'namespaces' => $this->namespaces,
			'excludeNamespaces' => $this->excludeNamespaces,
			'order' => $this->order,
			'direction' => $this->direction,
			'limit' => $this->limit,
			'headingLevel' => $this->headingLevel,
			'headingFormat' => $this->headingFormat,
			'excludeSelf' => $this->excludeSelf,
			'redirectMode' => $this->redirectMode,
			'fileMode' => $this->fileMode,
			'imageWidth' => $this->imageWidth,
			'emptyMessage' => $this->emptyMessage,
			'titleOnlyPatterns' => $this->titleOnlyPatterns,
		];
		return hash( 'sha256', json_encode( $data ) );
	}
}
