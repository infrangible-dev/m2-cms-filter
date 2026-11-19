<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Model\Template\Filter;

use Exception;
use Magento\Framework\Filter\DirectiveProcessorInterface;
use Magento\Framework\Filter\Template;
use Magento\Framework\Filter\VariableResolverInterface;

/**
 * The directive {{fif <variable>}}...{{fielse}}...{{/fif}} inside {{for}} loops, which checks a value of the current
 * entry of the loop.
 *
 * Magento processes {{if}} before {{for}}, with the variables outside the loop, so {{if}} cannot check the entry. Works
 * like Magento's {{if}} otherwise.
 */
class ForIfDirective implements DirectiveProcessorInterface
{
    /**
     * @param VariableResolverInterface $variableResolver
     */
    public function __construct(protected VariableResolverInterface $variableResolver)
    {
    }

    /**
     * Returns the filtered content of the condition, or of the else part if the value is empty.
     *
     * Empty values are null, false, an empty string or array and numeric zeros like 0 and "0". Magento's {{if}}
     * compares the value with an empty string, which treats 0 as set since PHP 8.
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
        if (count($templateVariables) === 0) {
            return $construction[ 0 ];
        }

        $value = $this->variableResolver->resolve($construction[ 1 ] ?? '', $filter, $templateVariables);

        if (! $this->isEmpty($value)) {
            return $this->filterContent($filter, $construction[ 2 ] ?? '');
        }

        if (isset($construction[ 3 ], $construction[ 4 ])) {
            return $this->filterContent($filter, $construction[ 4 ]);
        }

        return '';
    }

    /**
     * Returns the regular expression of the directive.
     *
     * @return string
     */
    public function getRegularExpression(): string
    {
        return '/{{fif\s*(.*?)}}(.*?)({{fielse}}(.*?))?{{\\/fif\s*}}/si';
    }

    /**
     * Checks whether the value of the condition is empty.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === false || $value === '' || $value === []) {
            return true;
        }

        return is_numeric($value) && (float)$value === 0.0;
    }

    /**
     * Returns the content filtered with the variables of the filter.
     *
     * Magento's template filter declares exceptions, but the filters of CMS content and e-mails catch them and return
     * a message instead, so this only applies to other filters, which get the content without the condition.
     *
     * @param Template $filter
     * @param string $content
     *
     * @return string
     */
    private function filterContent(Template $filter, string $content): string
    {
        try {
            return $filter->filter($content);
        } catch (Exception) {
            return '';
        }
    }
}
