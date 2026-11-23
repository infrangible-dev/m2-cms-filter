<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Plugin\Email\Model\Template;

use Infrangible\CmsFilter\Model\Template\Filter\HashTranslateDirective;
use Infrangible\CmsFilter\Model\Template\Filter\HashVarDirective;
use Magento\Email\Model\Template\Filter as EmailFilter;
use Magento\Framework\Filter\DirectiveProcessorInterface;
use WeakMap;

/**
 * Adds the directives #var# and #trans# and the modifier "filter" of {{var}} to the template filters of Magento (the
 * filters of e-mails, CMS pages and blocks and widgets).
 */
class Filter
{
    /**
     * The variables of each filter, which the filter does not return itself.
     *
     * @var WeakMap<EmailFilter, array<string, mixed>>
     */
    private WeakMap $templateVariables;

    /**
     * @param HashVarDirective $hashVarDirective
     * @param HashTranslateDirective $hashTranslateDirective
     */
    public function __construct(
        protected HashVarDirective $hashVarDirective,
        protected HashTranslateDirective $hashTranslateDirective
    ) {
        $this->templateVariables = new WeakMap();
    }

    /**
     * Remembers the variables of the filter.
     *
     * @param EmailFilter $subject
     * @param EmailFilter $result
     * @param array $variables
     * @phpstan-param array<string, mixed> $variables
     *
     * @return EmailFilter
     */
    public function afterSetVariables(EmailFilter $subject, EmailFilter $result, array $variables): EmailFilter
    {
        $this->templateVariables[ $subject ] = array_merge($this->templateVariables[ $subject ] ?? [], $variables);

        return $result;
    }

    /**
     * Replaces the directives #var# and #trans# before Magento processes the other directives.
     *
     * So they can be used in the parameters of other directives.
     *
     * @param EmailFilter $subject
     * @param mixed $value
     *
     * @return array
     * @phpstan-return array{0: mixed}
     */
    public function beforeFilter(EmailFilter $subject, mixed $value): array
    {
        if ($value === null) {
            return [''];
        }

        if (! is_string($value)) {
            // Magento reports the invalid value
            return [$value];
        }

        $templateVariables = $this->templateVariables[ $subject ] ?? [];

        $value = $this->replace($this->hashVarDirective, $value, $subject, $templateVariables);
        $value = $this->replace($this->hashTranslateDirective, $value, $subject, $templateVariables);

        return [$value];
    }

    /**
     * Filters the value of {{var}} again if the modifier "filter" is given.
     *
     * For example {{var content|filter}}: the directives of the value are processed as well.
     *
     * @param EmailFilter $subject
     * @param string|null $result
     * @param array $construction
     * @phpstan-param array<int|string, string> $construction
     *
     * @return string|null
     */
    public function afterVarDirective(EmailFilter $subject, ?string $result, array $construction = []): ?string
    {
        if ($result === null) {
            return null;
        }

        $modifiers = explode('|', ($construction[ 2 ] ?? '') . ($construction[ 'filters' ] ?? ''));

        array_shift($modifiers);

        if (! in_array('filter', array_map('trim', $modifiers), true)) {
            return $result;
        }

        return $subject->filter($result);
    }

    /**
     * Replaces the constructions of the directive in the value.
     *
     * @param DirectiveProcessorInterface $directive
     * @param string $value
     * @param EmailFilter $filter
     * @param array $templateVariables
     * @phpstan-param array<string, mixed> $templateVariables
     *
     * @return string
     */
    private function replace(
        DirectiveProcessorInterface $directive,
        string $value,
        EmailFilter $filter,
        array $templateVariables
    ): string {
        $result = preg_replace_callback(
            $directive->getRegularExpression(),
            static fn(array $construction): string => $directive->process(
                $construction,
                $filter,
                $templateVariables
            ),
            $value
        );

        return $result ?? $value;
    }
}
