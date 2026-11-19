<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Model\Template\Filter;

use Magento\Framework\DataObject;
use Magento\Framework\Filter\DirectiveProcessor\ForDirective as MagentoForDirective;
use Magento\Framework\Filter\Template;
use Magento\Framework\Filter\VariableResolverInterface;
use Stringable;

/**
 * The directive {{for <item> in <list>}}...{{/for}} of Magento with {{fif}} conditions inside the loop.
 *
 * Unlike Magento, the loop accepts lists that are not arrays (collections and other Traversable objects), and keeps the
 * content of entries without placeholders.
 */
class ForDirective extends MagentoForDirective
{
    /**
     * The placeholders in the content of a loop, like Magento's deprecated Template::CONSTRUCTION_PATTERN.
     */
    public const PLACEHOLDER_PATTERN = '/{{([a-z]{0,10})(.*?)}}(?:(.*?){{\/\1}})?/si';

    /**
     * @param VariableResolverInterface $loopResolver
     * @param ForIfDirective $forIfDirective
     */
    public function __construct(
        protected VariableResolverInterface $loopResolver,
        protected ForIfDirective $forIfDirective
    ) {
        parent::__construct($loopResolver);
    }

    /**
     * Returns the content of the loop for every entry of the list.
     *
     * The construction is returned unchanged if it is incomplete or if the list is neither an array nor Traversable.
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
        foreach (['loopBody', 'loopItem', 'loopData'] as $field) {
            if (trim($construction[ $field ] ?? '') === '') {
                return $construction[ 0 ];
            }
        }

        $loopData = $this->loopResolver->resolve($construction[ 'loopData' ], $filter, $templateVariables);

        if (! is_iterable($loopData)) {
            return $construction[ 0 ];
        }

        return $this->getLoopText(
            $loopData,
            (string)preg_replace('/\s+/', '', $construction[ 'loopItem' ]),
            $construction[ 'loopBody' ],
            $filter,
            $templateVariables
        );
    }

    /**
     * Returns the content of the loop for every entry that is an array or a data object.
     *
     * The entry and the variable "loop" (with the "index" of the entry, starting at 0) are also set as variables of the
     * filter, which processes the content of {{fif}}.
     *
     * @param iterable $loopData
     * @phpstan-param iterable<int|string, mixed> $loopData
     * @param string $loopItemName
     * @param string $loopBody
     * @param Template $filter
     * @param array $templateVariables
     * @phpstan-param array<string, mixed> $templateVariables
     *
     * @return string
     */
    protected function getLoopText(
        iterable $loopData,
        string $loopItemName,
        string $loopBody,
        Template $filter,
        array $templateVariables
    ): string {
        $loopText = [];
        $loopIndex = 0;
        $loop = new DataObject();

        foreach ($loopData as $loopItem) {
            if (is_array($loopItem)) {
                $loopItem = new DataObject($loopItem);
            }

            if (! $loopItem instanceof DataObject) {
                continue;
            }

            $loop->setData('index', $loopIndex++);

            $templateVariables[ 'loop' ] = $loop;
            $templateVariables[ $loopItemName ] = $loopItem;

            $filter->setVariables(['loop' => $loop, $loopItemName => $loopItem]);

            $loopText[] = $this->replaceVariables(
                $this->replaceConditions($loopBody, $filter, $templateVariables),
                $filter,
                $templateVariables
            );
        }

        return implode('', $loopText);
    }

    /**
     * Replaces the {{fif}} conditions of the content of the loop.
     *
     * @param string $text
     * @param Template $filter
     * @param array $templateVariables
     * @phpstan-param array<string, mixed> $templateVariables
     *
     * @return string
     */
    protected function replaceConditions(string $text, Template $filter, array $templateVariables): string
    {
        if (preg_match_all($this->forIfDirective->getRegularExpression(), $text, $constructions, PREG_SET_ORDER)) {
            foreach ($constructions as $construction) {
                $text = str_replace(
                    $construction[ 0 ],
                    $this->forIfDirective->process($construction, $filter, $templateVariables),
                    $text
                );
            }
        }

        return $text;
    }

    /**
     * Replaces the placeholders of the content of the loop with the values of the variables.
     *
     * Like Magento, the placeholders are not processed as directives.
     *
     * @param string $text
     * @param Template $filter
     * @param array $templateVariables
     * @phpstan-param array<string, mixed> $templateVariables
     *
     * @return string
     */
    protected function replaceVariables(string $text, Template $filter, array $templateVariables): string
    {
        if (preg_match_all(static::PLACEHOLDER_PATTERN, $text, $constructions, PREG_SET_ORDER)) {
            foreach ($constructions as $construction) {
                $value = $this->loopResolver->resolve($construction[ 2 ], $filter, $templateVariables);

                $text = str_replace($construction[ 0 ], $this->stringValue($value), $text);
            }
        }

        return $text;
    }

    /**
     * Returns the value of a variable as text: scalars and objects with __toString(), an empty string for other values.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function stringValue(mixed $value): string
    {
        return is_scalar($value) || $value instanceof Stringable ? (string)$value : '';
    }
}
