<?php

declare(strict_types=1);

namespace Infrangible\CmsFilter\Model\Template\Filter;

use Magento\Framework\Filter\DirectiveProcessor\IfDirective;

/**
 * @author  Andreas Knollmann
 */
class ForIfDirective
    extends IfDirective
{
    /**
     * @inheritdoc
     */
    public function getRegularExpression(): string
    {
        return '/{{fif\s*(.*?)}}(.*?)({{fielse}}(.*?))?{{\\/fif\s*}}/si';
    }
}
