<?php

require 'vendor/autoload.php';

use HtmlToAdf\ConverterManager;
use HtmlToAdf\Converters\HtmlConverter;
use HtmlToAdf\Converters\MarkdownConverter;
use HtmlToAdf\Converters\PlainTextConverter;

// Initialize the ADF Converter Manager
$manager = new ConverterManager();

// Register available converters
$manager->registerConverter(new HtmlConverter());
$manager->registerConverter(new MarkdownConverter());
$manager->registerConverter(new PlainTextConverter());

/**
 * ---------------------------
 * EXAMPLE 1 — HTML Conversion
 * ---------------------------
 */
$html = <<<HTML
<h1>Project Documentation</h1>
<p><strong>Important details</strong>, <em>notes</em>, <del>invalid info</del>, and <code>code snippets</code>.</p>
<ul>
  <li>Real-time data processing</li>
  <li>API integration
    <ul>
      <li>GET /api/items</li>
      <li>POST /api/items</li>
    </ul>
  </li>
  <li>Automated testing support</li>
</ul>
<ol>
  <li>Installation
    <ol>
      <li>Run <code>composer install</code></li>
      <li>Copy and configure <code>.env</code></li>
    </ol>
  </li>
</ol>
<blockquote><strong>Note:</strong> PHP 8.1 or above is required.</blockquote>
<pre><code class="language-js">fetch("https://api.example.com/data")
  .then(response => response.json())
  .then(data => console.log(data));</code></pre>
<img src="https://example.com/image.jpg" alt="Diagram" />
<table>
  <thead>
    <tr><th>Name</th><th>Role</th><th>Active</th></tr>
  </thead>
  <tbody>
    <tr><td>Cem</td><td>Admin</td><td>✅</td></tr>
    <tr><td>Ece</td><td>Editor</td><td>❌</td></tr>
  </tbody>
</table>
HTML;

echo "===== HTML to ADF =====\n";
$htmlAdf = $manager->convert('html', $html);
echo json_encode($htmlAdf, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

/**
 * ------------------------------
 * EXAMPLE 2 — Markdown Conversion
 * ------------------------------
 */
$markdown = <<<MD
# Markdown Example

**Bold**, _italic_, ~~strikethrough~~, and \`inline code\`

- List item 1
- List item 2
  - Nested list

> This is a blockquote

\`\`\`php
echo "Hello World!";
\`\`\`
MD;

echo "===== Markdown to ADF =====\n";
$markdownAdf = $manager->convert('markdown', $markdown);
echo json_encode($markdownAdf, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";


$text = "This is a simple plain text. It will be wrapped in a paragraph node.";

echo "===== Plain Text to ADF =====\n";
$textAdf = $manager->convert('text', $text);
echo json_encode($textAdf, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";
