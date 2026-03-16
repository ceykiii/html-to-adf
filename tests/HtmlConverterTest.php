<?php

namespace HtmlToAdf\Tests;

use PHPUnit\Framework\TestCase;
use HtmlToAdf\Converters\HtmlConverter;

class HtmlConverterTest extends TestCase
{
    private HtmlConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new HtmlConverter();
    }

    // --- supports() ---

    public function testSupportsHtml(): void
    {
        $this->assertTrue($this->converter->supports('html'));
        $this->assertTrue($this->converter->supports('HTML'));
        $this->assertTrue($this->converter->supports('Html'));
    }

    public function testDoesNotSupportOtherFormats(): void
    {
        $this->assertFalse($this->converter->supports('markdown'));
        $this->assertFalse($this->converter->supports('text'));
        $this->assertFalse($this->converter->supports('xml'));
    }

    // --- Paragraph ---

    public function testConvertParagraph(): void
    {
        $result = $this->converter->convert('<p>Hello World</p>');

        $this->assertSame('doc', $result['type']);
        $this->assertSame(1, $result['version']);
        $this->assertSame('paragraph', $result['content'][0]['type']);
        $this->assertSame('Hello World', $result['content'][0]['content'][0]['text']);
    }

    // --- Headings ---

    /**
     * @dataProvider headingProvider
     */
    public function testConvertHeadings(string $tag, int $level): void
    {
        $result = $this->converter->convert("<{$tag}>Title</{$tag}>");

        $node = $result['content'][0];
        $this->assertSame('heading', $node['type']);
        $this->assertSame($level, $node['attrs']['level']);
        $this->assertSame('Title', $node['content'][0]['text']);
    }

    public static function headingProvider(): array
    {
        return [
            ['h1', 1], ['h2', 2], ['h3', 3],
            ['h4', 4], ['h5', 5], ['h6', 6],
        ];
    }

    // --- Inline marks ---

    public function testConvertBoldText(): void
    {
        $result = $this->converter->convert('<p><strong>Bold</strong></p>');

        $text = $result['content'][0]['content'][0];
        $this->assertSame('Bold', $text['text']);
        $this->assertContains(['type' => 'strong'], $text['marks']);
    }

    public function testConvertBTag(): void
    {
        $result = $this->converter->convert('<p><b>Bold</b></p>');

        $text = $result['content'][0]['content'][0];
        $this->assertContains(['type' => 'strong'], $text['marks']);
    }

    public function testConvertItalicText(): void
    {
        $result = $this->converter->convert('<p><em>Italic</em></p>');

        $text = $result['content'][0]['content'][0];
        $this->assertSame('Italic', $text['text']);
        $this->assertContains(['type' => 'em'], $text['marks']);
    }

    public function testConvertITag(): void
    {
        $result = $this->converter->convert('<p><i>Italic</i></p>');

        $text = $result['content'][0]['content'][0];
        $this->assertContains(['type' => 'em'], $text['marks']);
    }

    public function testConvertInlineCode(): void
    {
        $result = $this->converter->convert('<p><code>snippet</code></p>');

        $text = $result['content'][0]['content'][0];
        $this->assertSame('snippet', $text['text']);
        $this->assertContains(['type' => 'code'], $text['marks']);
    }

    public function testConvertStrikethrough(): void
    {
        $result = $this->converter->convert('<p><del>deleted</del></p>');

        $text = $result['content'][0]['content'][0];
        $this->assertContains(['type' => 'strike'], $text['marks']);
    }

    public function testConvertSTag(): void
    {
        $result = $this->converter->convert('<p><s>striked</s></p>');

        $text = $result['content'][0]['content'][0];
        $this->assertContains(['type' => 'strike'], $text['marks']);
    }

    public function testConvertLink(): void
    {
        $result = $this->converter->convert('<p><a href="https://example.com">Link</a></p>');

        $text = $result['content'][0]['content'][0];
        $this->assertSame('Link', $text['text']);

        $linkMark = null;
        foreach ($text['marks'] as $mark) {
            if ($mark['type'] === 'link') {
                $linkMark = $mark;
                break;
            }
        }
        $this->assertNotNull($linkMark);
        $this->assertSame('https://example.com', $linkMark['attrs']['href']);
    }

    public function testConvertNestedMarks(): void
    {
        $result = $this->converter->convert('<p><strong><em>Bold Italic</em></strong></p>');

        $text = $result['content'][0]['content'][0];
        $this->assertSame('Bold Italic', $text['text']);

        $markTypes = array_column($text['marks'], 'type');
        $this->assertContains('strong', $markTypes);
        $this->assertContains('em', $markTypes);
    }

    public function testConvertHardBreak(): void
    {
        $result = $this->converter->convert('<p>Line1<br>Line2</p>');

        $content = $result['content'][0]['content'];
        $types = array_column($content, 'type');
        $this->assertContains('hardBreak', $types);
    }

    // --- Lists ---

    public function testConvertUnorderedList(): void
    {
        $result = $this->converter->convert('<ul><li>Item 1</li><li>Item 2</li></ul>');

        $list = $result['content'][0];
        $this->assertSame('bulletList', $list['type']);
        $this->assertCount(2, $list['content']);
        $this->assertSame('listItem', $list['content'][0]['type']);
    }

    public function testConvertOrderedList(): void
    {
        $result = $this->converter->convert('<ol><li>First</li><li>Second</li></ol>');

        $list = $result['content'][0];
        $this->assertSame('orderedList', $list['type']);
        $this->assertCount(2, $list['content']);
    }

    public function testConvertNestedList(): void
    {
        $html = '<ul><li>Parent<ul><li>Child</li></ul></li></ul>';
        $result = $this->converter->convert($html);

        $listItem = $result['content'][0]['content'][0];
        $this->assertSame('listItem', $listItem['type']);

        $nestedList = null;
        foreach ($listItem['content'] as $child) {
            if ($child['type'] === 'bulletList') {
                $nestedList = $child;
                break;
            }
        }
        $this->assertNotNull($nestedList);
    }

    // --- Blockquote ---

    public function testConvertBlockquote(): void
    {
        $result = $this->converter->convert('<blockquote><p>Quoted text</p></blockquote>');

        $bq = $result['content'][0];
        $this->assertSame('blockquote', $bq['type']);
        $this->assertSame('paragraph', $bq['content'][0]['type']);
    }

    // --- Code block ---

    public function testConvertCodeBlock(): void
    {
        $result = $this->converter->convert('<pre><code class="language-php">echo "hi";</code></pre>');

        $codeBlock = $result['content'][0];
        $this->assertSame('codeBlock', $codeBlock['type']);
        $this->assertSame('php', $codeBlock['attrs']['language']);
        $this->assertSame('echo "hi";', $codeBlock['content'][0]['text']);
    }

    public function testConvertCodeBlockWithoutLanguage(): void
    {
        $result = $this->converter->convert('<pre><code>plain code</code></pre>');

        $codeBlock = $result['content'][0];
        $this->assertSame('codeBlock', $codeBlock['type']);
        $this->assertInstanceOf(\stdClass::class, $codeBlock['attrs']);
    }

    public function testConvertPreWithoutCode(): void
    {
        $result = $this->converter->convert('<pre>raw preformatted</pre>');

        $codeBlock = $result['content'][0];
        $this->assertSame('codeBlock', $codeBlock['type']);
        $this->assertSame('raw preformatted', $codeBlock['content'][0]['text']);
    }

    // --- Horizontal rule ---

    public function testConvertHorizontalRule(): void
    {
        $result = $this->converter->convert('<hr>');

        $this->assertSame('rule', $result['content'][0]['type']);
    }

    // --- Image ---

    public function testConvertImage(): void
    {
        $result = $this->converter->convert('<img src="https://example.com/img.png" alt="Photo">');

        $media = $result['content'][0];
        $this->assertSame('mediaSingle', $media['type']);
        $this->assertSame('external', $media['content'][0]['attrs']['type']);
        $this->assertSame('https://example.com/img.png', $media['content'][0]['attrs']['url']);
    }

    // --- Table ---

    public function testConvertTable(): void
    {
        $html = '<table><thead><tr><th>Name</th><th>Age</th></tr></thead><tbody><tr><td>Cem</td><td>25</td></tr></tbody></table>';
        $result = $this->converter->convert($html);

        $table = $result['content'][0];
        $this->assertSame('table', $table['type']);

        $headerRow = $table['content'][0];
        $this->assertSame('tableRow', $headerRow['type']);
        $this->assertSame('tableHeader', $headerRow['content'][0]['type']);

        $dataRow = $table['content'][1];
        $this->assertSame('tableCell', $dataRow['content'][0]['type']);
    }

    // --- Edge cases ---

    public function testConvertEmptyHtml(): void
    {
        $result = $this->converter->convert('');

        $this->assertSame('doc', $result['type']);
        $this->assertEmpty($result['content']);
    }

    public function testConvertMultipleElements(): void
    {
        $html = '<h1>Title</h1><p>Paragraph</p><hr>';
        $result = $this->converter->convert($html);

        $this->assertCount(3, $result['content']);
        $this->assertSame('heading', $result['content'][0]['type']);
        $this->assertSame('paragraph', $result['content'][1]['type']);
        $this->assertSame('rule', $result['content'][2]['type']);
    }

    public function testConvertUnknownTagFallback(): void
    {
        $result = $this->converter->convert('<div>Fallback text</div>');

        $node = $result['content'][0];
        $this->assertSame('paragraph', $node['type']);
        $this->assertSame('Fallback text', $node['content'][0]['text']);
    }
}
