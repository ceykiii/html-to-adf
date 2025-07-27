<?php
namespace HtmlToAdf;

use HtmlToAdf\Contracts\ConverterInterface;

/**
 * Class ConverterManager
 *
 * Manages and delegates conversion requests to registered converters
 * based on supported input formats.
 *
 * @package HtmlToAdf
 * @author Cem Açar <cemacar03@gmail.com>
 */
class ConverterManager
{
    /**
     * @var ConverterInterface[] List of registered converters
     */
    protected array $converters = [];

    /**
     * Registers a converter to the manager.
     *
     * @param ConverterInterface $converter The converter instance to register
     * @return void
     */
    public function registerConverter(ConverterInterface $converter): void
    {
        $this->converters[] = $converter;
    }

    /**
     * Converts the given content using a matching converter for the specified format.
     *
     * @param string $inputFormat The format of the input content (e.g., 'html', 'markdown')
     * @param string $content The raw content to be converted
     * @return array Converted content in ADF format
     *
     * @throws \Exception If no suitable converter is found for the given format
     */
    public function convert(string $inputFormat, string $content): array
    {
        foreach ($this->converters as $converter) {
            if ($converter->supports($inputFormat)) {
                return $converter->convert($content);
            }
        }

        throw new \Exception("No converter found for format: $inputFormat");
    }
}
