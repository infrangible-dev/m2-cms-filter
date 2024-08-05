<?php

declare(strict_types=1);

namespace Infrangible\CmsFilter\Model\Template\Filter;

use Magento\Framework\Filter\DirectiveProcessor\VarDirective;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class HashVarDirective
    extends VarDirective
{
    public function getRegularExpression(): string
    {
        return '/#(var)(.*?)(?P<filters>(?:\|[a-z0-9:_-]+)+)?#/si';
    }
}
