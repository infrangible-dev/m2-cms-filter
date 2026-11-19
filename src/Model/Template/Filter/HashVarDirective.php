<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Model\Template\Filter;

use Magento\Framework\Filter\DirectiveProcessor\VarDirective;

/**
 * The directive #var <variable>#, for example in the parameters of a widget, which are written in curly braces
 * themselves.
 *
 * Works like Magento's {{var}} without escaping: filters are given like in {{var}}, for example #var name|escape#.
 */
class HashVarDirective extends VarDirective
{
    /**
     * Returns the regular expression of the directive.
     *
     * The variable follows after whitespace and contains no hash character, so that links to anchors like "#variants"
     * are not taken for a directive.
     *
     * @return string
     */
    public function getRegularExpression(): string
    {
        return '/#(var)(\s[^#]*?)(?P<filters>(?:\|[a-z0-9:_-]+)+)?#/si';
    }
}
