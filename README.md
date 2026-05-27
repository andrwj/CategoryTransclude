# CategoryTransclude (분류 삽입 확장 기능)

`CategoryTransclude`는 특정 카테고리(분류)에 속한 모든 문서들의 내용을 가져와 현재 페이지에 반복적으로 끼워넣어(Transclude) 렌더링해주는 미디어위키(MediaWiki) 확장 기능입니다. 
특정 주제로 분류된 문서들을 모아서 하나의 거대한 집계 페이지(Aggregate Page)나 요약 페이지를 구성할 때 유용하게 활용할 수 있습니다.

---

## 🚀 주요 특징

* **파서 함수 지원**: `{{#categorytransclude:...}}` 또는 한국어 매직 워드 `{{#분류삽입:...}}`을 통해 간편하게 호출할 수 있습니다.
* **유연한 필터링**: 네임스페이스 포함/제외 필터, 문서 타입 필터(일반 문서, 파일, 하위 카테고리) 등을 지원합니다.
* **리다이렉트 문서 제어**: 리다이렉트 문서를 건너뛰거나(`skip`), 타겟 문서를 추적하여 최종 도달 페이지를 가져오도록(`follow`) 설정할 수 있습니다.
* **정교한 캐시 무효화 및 의존성 추적**: 집계 대상 페이지와 참조 카테고리 간의 의존성을 데이터베이스(`category_transclude_deps`)로 관리합니다. 카테고리에 문서가 추가되거나 제거될 경우, 관련 집계 페이지의 캐시를 백그라운드 작업(`JobQueue`)을 통해 자동으로 무효화(Purge)하여 항상 최신 내용을 유지합니다.
* **다양한 파일 모드**: 파일 분류의 경우 단순 설명 문서 내용뿐만 아니라, 미디어를 직접 화면에 임베드(`embed`)하거나 링크 형태로 렌더링할 수 있습니다.

---

## 🛠️ 설치 방법

### 1. 확장 기능 복사
미디어위키 설치 경로의 `extensions/` 디렉토리 아래에 `CategoryTransclude` 폴더를 생성하고 소스 코드를 복사합니다.
```bash
/var/www/wiki/extensions/CategoryTransclude/
```

### 2. LocalSettings.php 등록
미디어위키 설정 파일인 `LocalSettings.php` 파일 하단에 아래 코드를 추가합니다.
```php
wfLoadExtension( 'CategoryTransclude' );
```

### 3. 데이터베이스 스키마 업데이트
의존성 관리를 위한 테이블(`category_transclude_deps`)을 생성하기 위해 시스템 업데이트 스크립트를 실행합니다.
```bash
php maintenance/update.php
```

---

## ⚙️ 설정 옵션

`LocalSettings.php`에서 아래 글로벌 설정 값들을 수정하여 기본 동작을 제어할 수 있습니다.

* **`$wgCategoryTranscludeDefaultCategory`** (기본값: `'Category:KontexusNote'`)
  * 파서 함수에서 대상 카테고리를 지정하지 않았을 때 사용할 기본 카테고리 명칭입니다.
* **`$wgCategoryTranscludeDefaultLimit`** (기본값: `200`)
  * 한 번에 transclude할 기본 카테고리 멤버 수의 제한치입니다.
* **`$wgCategoryTranscludeMaxLimit`** (기본값: `500`)
  * 안전을 위해 허용되는 최대 transclude 카테고리 멤버 수 제한치입니다.
* **`$wgCategoryTranscludeDefaultHeadingLevel`** (기본값: `2`)
  * 개별 문서 내용을 불러올 때 위에 붙여줄 문서 제목의 기본 Heading 레벨 (`H2`)입니다.
* **`$wgCategoryTranscludeEnableDependencyTracking`** (기본값: `true`)
  * 집계 페이지와 카테고리 간의 실시간 캐시 갱신을 위한 의존성 추적 기능을 활성화할지 여부입니다.

---

## 📖 사용 방법 (문법 및 매개변수)

### 기본 문법
```wikitext
{{#categorytransclude:분류_이름|매개변수1=값1|매개변수2=값2...}}
```
또는 한글 매직 워드를 사용합니다.
```wikitext
{{#분류삽입:분류_이름|매개변수1=값1|매개변수2=값2...}}
```

### 매개변수 목록

| 매개변수 | 설명 | 허용되는 값 | 기본값 |
|---|---|---|---|
| **첫 번째 인자** 또는 **`category`** | 수집할 대상 카테고리의 이름입니다. (예: `분류:도움말`, `Category:Manual`) | 문자열 | (설정의 기본 카테고리) |
| **`type`** | 가져올 카테고리 멤버 유형을 설정합니다. 쉼표(`,`)로 복수 선택이 가능합니다. | `page` (일반 문서), `file` (파일), `subcat` (하위 분류), `all` (전체) | `page` |
| **`namespace`** | 특정 네임스페이스 ID에 속한 문서만 수집합니다. 쉼표(`,`)로 구분합니다. | 예: `0` (일반), `4` (프로젝트) 등 | 제한 없음 (null) |
| **`exclude-namespace`**| 제외하고 싶은 네임스페이스 ID 목록을 지정합니다. 쉼표(`,`)로 구분합니다. | 예: `2` (사용자), `3` (토론) | 없음 (null) |
| **`order`** | 정렬 기준을 설정합니다. | `sortkey` (분류 정렬 키), `title` (문서 제목), `timestamp` (분류 추가일), `pageid` (문서 ID) | `sortkey` |
| **`dir`** | 정렬 방향을 설정합니다. | `asc` (오름차순), `desc` (내림차순) | `asc` |
| **`limit`** | 한 번에 불러올 문서의 최대 개수입니다. | 숫자 (최대 `$wgCategoryTranscludeMaxLimit`까지 적용) | `200` |
| **`heading`** | 각 개별 문서를 불러올 때 제목으로 감싸줄 HTML Heading Level입니다. | `0` (제목 표시 안 함), `1`~`6` | `2` (`H2`) |
| **`heading-format`** | 제목의 형태를 정의합니다. | `link` (해당 문서 링크 적용), `plain` (일반 텍스트), `none` (제목 숨김) | `link` |
| **`exclude-self`** | 현재 이 파서 함수를 사용하는 집계 페이지 자체가 카테고리에 들어있을 때, 무한 루프 및 중복 방지를 위해 본인 문서를 목록에서 제외할지 여부입니다. | `1` / `true` (제외함), `0` / `false` (포함함) | `1` (`true`) |
| **`redirect`** | 카테고리에 속한 문서 중 리다이렉트 문서를 처리하는 방법입니다. | `keep` (리다이렉트 원문 transclude), `skip` (목록에서 건너뜀), `follow` (리다이렉트 최종 목적지 문서 추적 및 transclude) | `keep` |
| **`file-mode`** | 멤버 유형이 파일(`file`)일 때 렌더링 스타일입니다. | `description` (설명 문서 텍스트 transclude), `embed` (이미지 직접 삽입), `link` (미디어 링크 생성) | `description` |
| **`image-width`** | `file-mode=embed`인 경우 임베드할 이미지 가로 폭 크기를 조절합니다. | 예: `400px`, `800px` | `800px` |
| **`empty-message`** | 대상 분류에 부합하는 문서가 하나도 없을 때 노출할 텍스트입니다. | 문자열 | 시스템 기본 메시지 |
| **`show-errors`** | 파싱 도중 오류 발생 시 사용자에게 디테일한 에러 메시지를 노출할지 설정합니다. | `1` / `true`, `0` / `false` | `0` (`false`) |
| **`debug`** | HTML 주석 형태로 렌더링 디버그 정보를 페이지 소스에 출력할지 여부입니다. | `1` / `true`, `0` / `false` | `0` (`false`) |

---

## 💡 사용 예시

### 예시 1: 가장 기본적인 일반 문서 수집
'보고서' 카테고리에 있는 일반 문서들의 내용을 순서대로 모아서 제목(`H2`)과 함께 출력합니다.
```wikitext
{{#분류삽입:분류:보고서}}
```

### 예시 2: 파일/이미지 갤러리 형태로 임베드
'스크린샷' 카테고리에 포함된 이미지들을 가로 400px 크기로 화면에 나란히 임베드합니다.
```wikitext
{{#분류삽입:분류:스크린샷|type=file|file-mode=embed|image-width=400px|heading=0}}
```

### 예시 3: 고도화된 보고서 조합 필터링
최근 분류에 등록된 순으로 정렬하여 10개만 불러오되, 리다이렉트 문서는 대상 문서를 쫓아가도록 처리하고 일반 문서(`NS_MAIN=0`)만 골라냅니다.
```wikitext
{{#분류삽입:분류:공지사항
  |namespace=0
  |order=timestamp
  |dir=desc
  |limit=10
  |redirect=follow
  |heading=3
  |heading-format=plain
}}
```

---

## 🔒 라이선스

본 확장 기능은 **MIT 라이선스** 하에 제공됩니다. 자유롭게 변경 및 배포가 가능합니다.
