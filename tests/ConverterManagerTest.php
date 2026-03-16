<?php

namespace HtmlToAdf\Tests;

use PHPUnit\Framework\TestCase;
use HtmlToAdf\ConverterManager;
use HtmlToAdf\Converters\HtmlConverter;
use HtmlToAdf\Converters\MarkdownConverter;
use HtmlToAdf\Converters\PlainTextConverter;

class ConverterManagerTest extends TestCase
{
    private ConverterManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ConverterManager();
        $this->manager->registerConverter(new HtmlConverter());
        $this->manager->registerConverter(new MarkdownConverter());
        $this->manager->registerConverter(new PlainTextConverter());
    }

    public function testConvertHtmlFormat(): void
    {
        $result = $this->manager->convert('html', '<p>Hello</p>');

        $this->assertSame('doc', $result['type']);
        $this->assertSame(1, $result['version']);
        $this->assertNotEmpty($result['content']);
    }

    public function testConvertMarkdownFormat(): void
    {
        $result = $this->manager->convert('markdown', '# Title');

        $this->assertSame('doc', $result['type']);
        $this->assertSame(1, $result['version']);
    }

    public function testConvertTextFormat(): void
    {
        $result = $this->manager->convert('text', 'Hello world');

        $this->assertSame('doc', $result['type']);
        $this->assertSame(1, $result['version']);
    }

    public function testThrowsExceptionForUnsupportedFormat(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No converter found for format: xml');

        $this->manager->convert('xml', '<root/>');
    }

    public function testRegisterConverterAddsConverter(): void
    {
        $manager = new ConverterManager();

        $this->expectException(\Exception::class);
        $manager->convert('html', '<p>Test</p>');
    }
}
