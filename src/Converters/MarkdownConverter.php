<?php
namespace HtmlToAdf\Converters;

use HtmlToAdf\Contracts\ConverterInterface;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use HtmlToAdf\Converters\HtmlConverter;

/**
 * Class MarkdownConverter
 *
 * Converts Markdown content into Atlassian Document Format (ADF)
 * by first transforming Markdown into HTML and then delegating
 * the transformation to HtmlConverter.
 *
 * @package HtmlToAdf\Converters
 * @author Cem Açar <cemacar03@gmail.com>
 */
class MarkdownConverter implements ConverterInterface
{
    /**
     * Markdown parser instance using CommonMark.
     *
     * @var CommonMarkConverter
     */
    protected CommonMarkConverter $parser;

    /**
     * HTML converter used to transform intermediate HTML to ADF.
     *
     * @var HtmlConverter
     */
    protected HtmlConverter $htmlConverter;

    /**
     * MarkdownConverter constructor.
     *
     * Initializes the CommonMark environment with core, table,
     * strikethrough, and task list extensions.
     */
    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new TaskListExtension());

        $this->parser = new CommonMarkConverter([], $environment);
        $this->htmlConverter = new HtmlConverter();
    }

    /**
     * Checks if this converter supports the given input format.
     *
     * @param string $inputFormat The input format string (e.g., "markdown")
     * @return bool True if supported, false otherwise
     */
    public function supports(string $inputFormat): bool
    {
        return strtolower($inputFormat) === 'markdown';
    }

    /**
     * Converts Markdown content into ADF by first converting it to HTML,
     * then delegating to HtmlConverter.
     *
     * @param string $content The raw Markdown content
     * @return array ADF-formatted array structure
     */
    public function convert(string $content): array
    {
        $html = method_exists($this->parser, 'convertToHtml')
            ? $this->parser->convertToHtml($content)
            : $this->parser->convert($content)->getContent();

        return $this->htmlConverter->convert($html);
    }
}
