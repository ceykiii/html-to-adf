<?php

namespace HtmlToAdf\Tests;

use PHPUnit\Framework\TestCase;
use HtmlToAdf\Converters\PlainTextConverter;

class PlainTextConverterTest extends TestCase
{
    private PlainTextConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new PlainTextConverter();
    }

    public function testSupportsText(): void
    {
        $this->assertTrue($this->converter->supports('text'));
        $this->assertTrue($this->converter->supports('TEXT'));
        $this->assertTrue($this->converter->supports('Text'));
    }

    public function testDoesNotSupportOtherFormats(): void
    {
        $this->assertFalse($this->converter->supports('html'));
        $this->assertFalse($this->converter->supports('markdown'));
    }

    public function testConvertPlainText(): void
    {
        $result = $this->converter->convert('Hello World');

        $this->assertSame(1, $result['version']);
        $this->assertSame('doc', $result['type']);
        $this->assertCount(1, $result['content']);
        $this->assertSame('paragraph', $result['content'][0]['type']);
        $this->assertSame('text', $result['content'][0]['content'][0]['type']);
        $this->assertSame('Hello World', $result['content'][0]['content'][0]['text']);
    }

    public function testConvertEmptyString(): void
    {
        $result = $this->converter->convert('');

        $this->assertSame('doc', $result['type']);
        $this->assertSame('', $result['content'][0]['content'][0]['text']);
    }

    public function testConvertMultilineText(): void
    {
        $text = "Line 1\nLine 2\nLine 3";
        $result = $this->converter->convert($text);

        $this->assertSame($text, $result['content'][0]['content'][0]['text']);
    }

    public function testConvertSpecialCharacters(): void
    {
        $text = 'Special chars: <>&"\'';
        $result = $this->converter->convert($text);

        $this->assertSame($text, $result['content'][0]['content'][0]['text']);
    }

    public function testConvertUnicodeText(): void
    {
        $text = 'Merhaba Dunya';
        $result = $this->converter->convert($text);

        $this->assertSame($text, $result['content'][0]['content'][0]['text']);
    }
}
