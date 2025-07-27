<?php
namespace HtmlToAdf\Contracts;

/**
 * Interface ConverterInterface
 *
 * Defines the contract for a converter that checks if a specific input format is supported
 * and converts the given content into Atlassian Document Format (ADF).
 *
 * @package HtmlToAdf\Contracts
 * @author Cem Açar <cemacar03@gmail.com>
 */
interface ConverterInterface
{
    /**
     * Determines whether the given input format is supported by the converter.
     *
     * @param string $inputFormat The input format to check (e.g., 'html', 'markdown')
     * @return bool True if the format is supported, false otherwise
     */
    public function supports(string $inputFormat): bool;

    /**
     * Converts the given content into Atlassian Document Format (ADF).
     *
     * @param string $content The content to convert
     * @return array The content converted into ADF structure
     */
    public function convert(string $content): array;
}
