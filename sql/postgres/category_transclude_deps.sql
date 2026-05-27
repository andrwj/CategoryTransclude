-- PostgreSQL용 CategoryTransclude 의존성 관리 테이블 스키마
CREATE TABLE /*_*/category_transclude_deps (
  ctd_page_id INTEGER NOT NULL,
  ctd_category BYTEA NOT NULL,
  ctd_params_hash BYTEA NOT NULL,
  ctd_touched BYTEA NOT NULL,
  PRIMARY KEY (ctd_page_id, ctd_category, ctd_params_hash)
);

CREATE INDEX ctd_category_page ON /*_*/category_transclude_deps (ctd_category, ctd_page_id);
