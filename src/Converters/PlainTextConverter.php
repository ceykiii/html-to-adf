<?php
namespace HtmlToAdf\Converters;

use HtmlToAdf\Contracts\ConverterInterface;

/**
 * Class PlainTextConverter
 *
 * Converts plain text content into a minimal Atlassian Document Format (ADF)
 * document structure. Wraps the text in a single paragraph block.
 *
 * @package HtmlToAdf\Converters
 * @author Cem Açar <cemacar03@gmail.com>
 */
class PlainTextConverter implements ConverterInterface
{
    /**
     * Determines if the converter supports the given input format.
     *
     * @param string $inputFormat The input format (e.g., "text")
     * @return bool True if supported, false otherwise
     */
    public function supports(string $inputFormat): bool
    {
        return strtolower($inputFormat) === 'text';
    }

    /**
     * Converts plain text into a basic ADF structure.
     *
     * @param string $content The plain text content to convert
     * @return array ADF document structure containing the text in a paragraph
     */
    public function convert(string $content): array
    {
        return [
            'version' => 1,
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => $content
                ]]
            ]]
        ];
    }
}
