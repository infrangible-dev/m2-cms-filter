<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Test\Unit\Model\Template\Filter;

use Magento\Framework\DataObject;
use Magento\Framework\Filter\Template;
use Magento\Framework\Filter\VariableResolverInterface;

/**
 * Resolves variables like "store.name" from arrays and data objects, a simple replacement for Magento's resolvers,
 * which need the tokenizer of the object manager. Unlike Magento's strict resolver, objects are returned as well.
 */
class ArrayResolver implements VariableResolverInterface
{
    /**
     * @param string $value
     * @param Template $filter
     * @param array $templateVariables
     * @phpstan-param array<string, mixed> $templateVariables
     *
     * @return mixed
     */
    public function resolve(string $value, Template $filter, array $templateVariables): mixed
    {
        $result = $templateVariables;

        foreach (explode('.', trim($value)) as $name) {
            if (is_array($result)) {
                $result = $result[ $name ] ?? null;
            } elseif ($result instanceof DataObject) {
                $result = $result->getData($name);
            } else {
                return null;
            }
        }

        return $result;
    }
}
