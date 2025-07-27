<?php
namespace HtmlToAdf\Converters;

use HtmlToAdf\Contracts\ConverterInterface;

/**
 * Class HtmlConverter
 *
 * Converts HTML content into Atlassian Document Format (ADF) structure.
 *
 * @package HtmlToAdf\Converters
 * @author Cem Açar <cemacar03@gmail.com>
 */
class HtmlConverter implements ConverterInterface
{
    /**
     * Checks whether the converter supports the provided input format.
     *
     * @param string $inputFormat The input format string (e.g., 'html')
     * @return bool True if the format is supported, false otherwise
     */
    public function supports(string $inputFormat): bool
    {
        return strtolower($inputFormat) === 'html';
    }


    /**
     * Converts HTML string content into an ADF document structure.
     *
     * @param string $content The raw HTML content
     * @return array ADF document as an associative array
     */
    public function convert(string $content): array
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $content);
        $body = $doc->getElementsByTagName('body')->item(0);

        $adfNodes = [];

        foreach ($body->childNodes as $node) {
            $converted = $this->convertNode($node);
            if ($converted) {
                $adfNodes[] = $converted;
            }
        }

        return [
            'version' => 1,
            'type' => 'doc',
            'content' => $adfNodes
        ];
    }

    /**
     * Converts a single DOM node into an ADF node.
     *
     * @param \DOMNode $node The DOM node to convert
     * @return array|null ADF node or null if not applicable
     */
    private function convertNode(\DOMNode $node): ?array
    {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            switch (strtolower($node->nodeName)) {
                case 'p':
                    return [
                        'type' => 'paragraph',
                        'content' => $this->convertInline($node)
                    ];
                case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
                    $level = (int)substr($node->nodeName, 1);
                    return [
                        'type' => 'heading',
                        'attrs' => ['level' => $level],
                        'content' => $this->convertInline($node)
                    ];
                case 'blockquote':
                    return [
                        'type' => 'blockquote',
                        'content' => $this->convertBlockChildren($node)
                    ];
                case 'pre':
                    return $this->convertPre($node);
                case 'ul':
                    return [
                        'type' => 'bulletList',
                        'content' => $this->convertListItems($node)
                    ];
                case 'ol':
                    return [
                        'type' => 'orderedList',
                        'content' => $this->convertListItems($node)
                    ];
                case 'hr':
                    return ['type' => 'rule'];
                case 'img':
                    return [
                        'type' => 'mediaSingle',
                        'content' => [[
                            'type' => 'media',
                            'attrs' => [
                                'type' => 'external',
                                'url' => $node->getAttribute('src')
                            ]
                        ]]
                    ];
                case 'table':
                    return [
                        'type' => 'table',
                        'content' => $this->convertTable($node)
                    ];
                default:
                    return $this->convertFallback($node);
            }
        }

        return null;
    }

     /**
     * Recursively converts all block-level children of a DOM node.
     *
     * @param \DOMNode $node The parent DOM node
     * @return array List of ADF nodes converted from children
     */
    private function convertBlockChildren(\DOMNode $node): array
    {
        $content = [];

        foreach ($node->childNodes as $child) {
            $converted = $this->convertNode($child);
            if ($converted) {
                $content[] = $converted;
            }
        }

        return $content;
    }


    /**
     * Converts a <pre> (and optionally nested <code>) block into ADF codeBlock.
     *
     * @param \DOMNode $node The <pre> DOM node
     * @return array ADF codeBlock node
     */
    private function convertPre(\DOMNode $node): array
    {
        $codeNode = null;

        foreach ($node->childNodes as $child) {
            if ($child->nodeName === 'code') {
                $codeNode = $child;
                break;
            }
        }

        $codeText = $codeNode
            ? $codeNode->textContent
            : $node->textContent;

        $language = null;
        if ($codeNode instanceof \DOMElement) {
            $class = $codeNode->getAttribute('class');
            if (preg_match('/language-(\w+)/', $class, $matches)) {
                $language = $matches[1];
            }
        }

        return [
            'type' => 'codeBlock',
            'attrs' => $language ? ['language' => $language] : new \stdClass(),
            'content' => [[
                'type' => 'text',
                'text' => trim($codeText)
            ]]
        ];
    }

     /**
     * Converts inline content within a DOM node (text, marks, links, etc).
     *
     * @param \DOMNode $node The DOM node containing inline content
     * @return array List of ADF inline nodes
     */
    private function convertInline(\DOMNode $node): array
    {
        $content = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->nodeValue;
                if (trim($text) !== '') {
                    $content[] = ['type' => 'text', 'text' => $text];
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                if ($child->nodeName === 'br') {
                    $content[] = ['type' => 'hardBreak'];
                    continue;
                }

                $inner = $this->convertInline($child);
                foreach ($inner as &$piece) {
                    if (!isset($piece['marks'])) {
                        $piece['marks'] = [];
                    }
                    if (in_array($child->nodeName, ['strong', 'b'])) {
                        $piece['marks'][] = ['type' => 'strong'];
                    }
                    if (in_array($child->nodeName, ['em', 'i'])) {
                        $piece['marks'][] = ['type' => 'em'];
                    }
                    if ($child->nodeName === 'code') {
                        $piece['marks'][] = ['type' => 'code'];
                    }
                    if (in_array($child->nodeName, ['s', 'del', 'strike'])) {
                        $piece['marks'][] = ['type' => 'strike'];
                    }
                    if ($child->nodeName === 'a') {
                        $href = $child->getAttribute('href');
                        $piece['marks'][] = ['type' => 'link', 'attrs' => ['href' => $href]];
                    }
                }
                $content = array_merge($content, $inner);
            }
        }

        return $content;
    }

    /**
     * Converts list items (<ul>/<ol>) into ADF listItem structures.
     *
     * @param \DOMNode $node The list container node
     * @return array List of ADF listItem nodes
     */
    private function convertListItems(\DOMNode $node): array
    {
        $items = [];

        foreach ($node->childNodes as $li) {
            if ($li->nodeName !== 'li') continue;

            $children = [];
            $inline = $this->convertInline($li);
            if (!empty($inline)) {
                $children[] = [
                    'type' => 'paragraph',
                    'content' => $inline
                ];
            }

            foreach ($li->childNodes as $child) {
                if (in_array($child->nodeName, ['ul', 'ol'])) {
                    $nested = $this->convertNode($child);
                    if ($nested) {
                        $children[] = $nested;
                    }
                }
            }

            $items[] = [
                'type' => 'listItem',
                'content' => $children
            ];
        }

        return $items;
    }

    /**
     * Converts an HTML table into an ADF-compliant table structure.
     *
     * @param \DOMNode $table The <table> DOM node
     * @return array ADF table content (rows and cells)
     */
    private function convertTable(\DOMNode $table): array
    {
        $rows = [];

        foreach ($table->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $td) {
                if (!in_array($td->nodeName, ['td', 'th'])) continue;

                $cellType = $td->nodeName === 'th' ? 'tableHeader' : 'tableCell';
                $cells[] = [
                    'type' => $cellType,
                    'content' => [[
                        'type' => 'paragraph',
                        'content' => $this->convertInline($td)
                    ]]
                ];
            }

            $rows[] = [
                'type' => 'tableRow',
                'content' => $cells
            ];
        }

        return $rows;
    }

    /**
     * Provides a fallback conversion for unknown or unsupported nodes.
     * Wraps the content in a simple paragraph node if possible.
     *
     * @param \DOMNode $node The unknown DOM node
     * @return array|null Fallback ADF paragraph node or null
     */ 
    private function convertFallback(\DOMNode $node): ?array
    {
        $inline = $this->convertInline($node);
        if (!empty($inline)) {
            return [
                'type' => 'paragraph',
                'content' => $inline
            ];
        }

        return null;
    }
}
