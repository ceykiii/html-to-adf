# html-to-adf

A PHP library to convert **HTML**, **Markdown**, and **Plain Text** into Atlassian Document Format (ADF) — compatible with Jira & Confluence.

---

## Features

- Recursive HTML parsing via DOM
- Headings, Paragraphs, Emphasis, Strong, Links, Lists, Code Blocks, Tables, Images
- Markdown-to-ADF using `league/commonmark`
- Plain text support
- Pluggable converter architecture
- Composer-based install
- Testable, extensible & PSR-compliant

---

## Installation

```bash
composer require your-vendor/html-to-adf
```

---

## Usage

### Initialize `ConverterManager` and register converters

```php
use HtmlToAdf\ConverterManager;
use HtmlToAdf\Converters\HtmlConverter;
use HtmlToAdf\Converters\MarkdownConverter;
use HtmlToAdf\Converters\PlainTextConverter;

$manager = new ConverterManager();
$manager->registerConverter(new HtmlConverter());
$manager->registerConverter(new MarkdownConverter());
$manager->registerConverter(new PlainTextConverter());
```

---

### Convert HTML

```php
$html = '<h2>Hello</h2><p>This is a <strong>test</strong>.</p>';
$adf = $manager->convert('html', $html);

print_r($adf);
```

---

### Convert Markdown

```php
$markdown = "## Hello\nThis is a **test**.";
$adf = $manager->convert('markdown', $markdown);

print_r($adf);
```

---

### Convert Plain Text

```php
$text = "Just a simple paragraph.";
$adf = $manager->convert('text', $text);

print_r($adf);
```

---

## ADF Output Example

**Input:**

```html
<p>Hello <strong>world</strong>!</p>
```

**Output:**

```json
{
  "version": 1,
  "type": "doc",
  "content": [
    {
      "type": "paragraph",
      "content": [
        { "type": "text", "text": "Hello " },
        {
          "type": "text",
          "text": "world",
          "marks": [{ "type": "strong" }]
        },
        { "type": "text", "text": "!" }
      ]
    }
  ]
}
```

---

## Architecture

| Component             | Description                                    |
|----------------------|------------------------------------------------|
| `ConverterManager`   | Manages format dispatching to converters       |
| `ConverterInterface` | Interface for pluggable format converters      |
| `HtmlConverter`      | Parses DOM and builds ADF recursively          |
| `MarkdownConverter`  | Uses CommonMark, then delegates to HTML        |
| `PlainTextConverter` | Wraps text in paragraph ADF structure          |
| `AdfNodeBuilder`     | (Optional) Helps build consistent ADF output   |



---

## Author

**Cem Açar**  
cemacar03@gmail.com

---

## License

Licensed under the [MIT License](LICENSE).
