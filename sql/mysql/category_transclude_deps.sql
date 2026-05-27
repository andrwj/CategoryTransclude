-- MySQL/MariaDB용 CategoryTransclude 의존성 관리 테이블 스키마
-- 집계 페이지가 어떤 카테고리와 인자 조합에 의존하는지 기록합니다.
CREATE TABLE /*_*/category_transclude_deps (
  -- 집계 대상 페이지의 ID (page.page_id 참조)
  ctd_page_id INT UNSIGNED NOT NULL,
  -- 참조하는 카테고리의 DB 키 명칭
  ctd_category VARBINARY(255) NOT NULL,
  -- 파라미터 옵션 조합의 sha256 해시값
  ctd_params_hash VARBINARY(64) NOT NULL,
  -- 마지막으로 의존성이 갱신된 미디어위키 타임스탬프
  ctd_touched BINARY(14) NOT NULL,
  PRIMARY KEY (ctd_page_id, ctd_category, ctd_params_hash),
  KEY ctd_category_page (ctd_category, ctd_page_id)
) /*$wgDBTableOptions*/;
