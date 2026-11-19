<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Model\Template\Filter;

use Magento\Framework\Filter\DirectiveProcessorInterface;
use Magento\Framework\Filter\Template;

/**
 * The directive #trans "<text>"#, which translates the text into the language of the store view, for example in the
 * parameters of a widget, which are written in curly braces themselves.
 */
class HashTranslateDirective implements DirectiveProcessorInterface
{
    /**
     * Returns the translated text.
     *
     * @param array $construction
     * @phpstan-param array<int|string, string> $construction
     * @param Template $filter
     * @param array $templateVariables
     * @phpstan-param array<string, mixed> $templateVariables
     *
     * @return string
     */
    public function process(array $construction, Template $filter, array $templateVariables): string
    {
        $text = $construction[ 1 ] ?? '';

        if ($text === '') {
            return $construction[ 0 ];
        }

        return __($text)->render();
    }

    /**
     * Returns the regular expression of the directive.
     *
     * The text follows after whitespace in double quotes and contains no hash character and no double quote.
     *
     * @return string
     */
    public function getRegularExpression(): string
    {
        return '/#trans\s+"([^"#]*)"#/si';
    }
}
