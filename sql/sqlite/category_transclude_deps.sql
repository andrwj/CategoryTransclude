-- SQLite용 CategoryTransclude 의존성 관리 테이블 스키마
CREATE TABLE /*_*/category_transclude_deps (
  ctd_page_id INTEGER UNSIGNED NOT NULL,
  ctd_category BLOB NOT NULL,
  ctd_params_hash BLOB NOT NULL,
  ctd_touched BLOB NOT NULL,
  PRIMARY KEY (ctd_page_id, ctd_category, ctd_params_hash)
);

CREATE INDEX /*i*/ctd_category_page ON /*_*/category_transclude_deps (ctd_category, ctd_page_id);
