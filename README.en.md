# CategoryTransclude

`CategoryTransclude` is a MediaWiki extension that fetches and transcludes the contents of all pages within a specific category, rendering them sequentially on a single page. It is highly useful for creating aggregate pages, summary logs, or unified documents out of categorized wiki pages.

---

## 🚀 Key Features

* **Parser Function Support**: Easily call it using `{{#categorytransclude:...}}` or the Korean alias `{{#분류삽입:...}}`.
* **Flexible Filtering**: Filter by member types (`page`, `file`, `subcat`) or namespace lists (include/exclude).
* **Redirect Page Control**: Choose to keep redirect syntax, skip them entirely, or follow them to display the final target page's content.
* **Cache Invalidation & Dependency Tracking**: Manages dependencies between the aggregate page and categorized pages via a database table (`category_transclude_deps`). When category members are added or removed, it automatically purges the cache of the depending aggregate pages in the background using the `JobQueue`.
* **Custom File Rendering**: For file types, choose between transcluding the file description text, embedding the media directly, or providing a media link.

---

## 🛠️ Installation

### 1. Copy the Extension
Clone or copy the `CategoryTransclude` directory to your MediaWiki installations `extensions/` directory:
```bash
/var/www/wiki/extensions/CategoryTransclude/
```

### 2. Register in LocalSettings.php
Add the following line to the bottom of your `LocalSettings.php` file:
```php
wfLoadExtension( 'CategoryTransclude' );
```

### 3. Update Database Schema
Run the maintenance update script to automatically create the required `category_transclude_deps` database table:
```bash
php maintenance/update.php
```

---

## ⚙️ Configuration Settings

You can customize the default behavior by modifying these global variables in `LocalSettings.php`:

* **`$wgCategoryTranscludeDefaultCategory`** (Default: `'Category:KontexusNote'`)
  * The default category name to use if no category is specified in the parser function.
* **`$wgCategoryTranscludeDefaultLimit`** (Default: `200`)
  * The default limit of category members to transclude.
* **`$wgCategoryTranscludeMaxLimit`** (Default: `500`)
  * The maximum limit of category members allowed to transclude for performance reasons.
* **`$wgCategoryTranscludeDefaultHeadingLevel`** (Default: `2`)
  * The default heading level (`H2`) used to wrap each transcluded page title.
* **`$wgCategoryTranscludeEnableDependencyTracking`** (Default: `true`)
  * Activates real-time cache updates by logging and tracking the dependencies of aggregate pages.

---

## 📖 Usage (Syntax & Parameters)

### Basic Syntax
```wikitext
{{#categorytransclude:Category_Name|param1=val1|param2=val2...}}
```
Or use the Korean magic word alias:
```wikitext
{{#분류삽입:분류_이름|param1=val1|param2=val2...}}
```

### Parameters

| Parameter | Description | Allowed Values | Default Value |
|---|---|---|---|
| **First Positional** or **`category`** | The name of the target category. (e.g., `Category:Reports`, `분류:도움말`) | String | (Configured Default Category) |
| **`type`** | The type of category members to fetch. Can combine multiple values separated by commas. | `page` (articles), `file` (files/images), `subcat` (subcategories), `all` (all types) | `page` |
| **`namespace`** | Restricts results to specific namespace IDs. Comma-separated list. | e.g. `0` (Main), `4` (Project) | No restriction (null) |
| **`exclude-namespace`**| Excludes pages from specific namespace IDs. Comma-separated list. | e.g. `2` (User), `3` (User Talk) | None (null) |
| **`order`** | Field to sort the transcluded pages. | `sortkey` (category sortkey), `title` (page title), `timestamp` (time added to category), `pageid` (page ID) | `sortkey` |
| **`dir`** | Sorting direction. | `asc` (ascending), `desc` (descending) | `asc` |
| **`limit`** | The maximum number of pages to transclude. | Integer (capped at `$wgCategoryTranscludeMaxLimit`) | `200` |
| **`heading`** | The HTML heading level used to display the page title. | `0` (hide title entirely), `1` to `6` | `2` (`H2`) |
| **`heading-format`** | Formatting style of the title headings. | `link` (linked to source page), `plain` (plain text, no link), `none` (hidden) | `link` |
| **`exclude-self`** | Excludes the aggregate page itself from the transclusion list to prevent infinite loops and duplicates. | `1` / `true` (exclude), `0` / `false` (include) | `1` (`true`) |
| **`redirect`** | How to handle redirect pages in the category. | `keep` (transclude redirect content), `skip` (omit redirects), `follow` (track 1-level redirect target and transclude it) | `keep` |
| **`file-mode`** | Formatting style for files (when `type` includes `file`). | `description` (transclude description page), `embed` (embed image directly), `link` (render media link) | `description` |
| **`image-width`** | Image width when `file-mode=embed` is used. | e.g. `400px`, `800px` | `800px` |
| **`empty-message`** | Text to display if no category members match the criteria. | String | System default message |
| **`show-errors`** | Displays detailed parser error messages to users if set to true. | `1` / `true`, `0` / `false` | `0` (`false`) |
| **`debug`** | Appends HTML comment-based debugging info to the rendered source. | `1` / `true`, `0` / `false` | `0` (`false`) |
| **`title-only`** | Renders only the heading (title) for matched pages, without transcluding their content. Items are separated by `;`. Use `%` as a wildcard to match any characters. Pages without a namespace prefix are assumed to be in the Main namespace. If `heading=0` or `heading-format=none`, matched pages are skipped entirely (no output). | e.g. `ReportA;6D,1%;Template:Header%` | None |

---

## 💡 Examples

### Example 1: Basic transclusion
Aggregates general articles from the "Reports" category, ordering them by category sortkey, with H2 titles.
```wikitext
{{#categorytransclude:Category:Reports}}
```

### Example 2: Embed an image gallery
Displays images from the "Screenshots" category embedded directly with 400px width, hiding the title headings.
```wikitext
{{#categorytransclude:Category:Screenshots|type=file|file-mode=embed|image-width=400px|heading=0}}
```

### Example 3: Advanced transclusion with filtering and sorting
Collects up to 10 articles from the Main namespace (`namespace=0`) within "Announcements", ordered by category addition time (descending), following any redirects to their target pages.
```wikitext
{{#categorytransclude:Category:Announcements
  |namespace=0
  |order=timestamp
  |dir=desc
  |limit=10
  |redirect=follow
  |heading=3
  |heading-format=plain
}}
```

### Example 4: Show title only for specific pages
Pages matching `6D,1%` (Main namespace) and `Template:Header` will render only their heading link, while all other category members are fully transcluded.
```wikitext
{{#categorytransclude:Category:Reports
  |title-only=6D,1%;Template:Header
  |heading=2
  |heading-format=link
}}
```

---

## 🔒 License

This extension is licensed under the **MIT License**. Feel free to use, modify, and redistribute.
