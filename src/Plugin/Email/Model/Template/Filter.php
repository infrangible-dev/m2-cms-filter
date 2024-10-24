<?php

declare(strict_types=1);

namespace Infrangible\CmsFilter\Plugin\Email\Model\Template;

use FeWeDev\Base\Arrays;
use Infrangible\CmsFilter\Model\Template\Filter\HashTranslateDirective;
use Infrangible\CmsFilter\Model\Template\Filter\HashVarDirective;
use Magento\Framework\App\ObjectManager;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Filter
{
    /** @var Arrays */
    protected $arrays;

    private $templateVars = [];

    public function __construct(Arrays $arrays)
    {
        $this->arrays = $arrays;
    }

    public function afterSetVariables(
        \Magento\Email\Model\Template\Filter $subject,
        \Magento\Email\Model\Template\Filter $result,
        array $variables
    ): \Magento\Email\Model\Template\Filter {
        $hash = spl_object_hash($subject);

        foreach ($variables as $name => $value) {
            $this->templateVars[ $hash ][ $name ] = $value;
        }

        return $result;
    }

    public function beforeFilter(\Magento\Email\Model\Template\Filter $subject, $value): array
    {
        if ($value === null) {
            return [''];
        }

        $hash = spl_object_hash($subject);

        /** @var HashVarDirective $hashVarDirective */
        $hashVarDirective = ObjectManager::getInstance()->get(HashVarDirective::class);

        if (preg_match_all(
            $hashVarDirective->getRegularExpression(),
            (string)$value,
            $constructions,
            PREG_SET_ORDER
        )) {
            foreach ($constructions as $construction) {
                $replacedValue = $hashVarDirective->process(
                    $construction,
                    $subject,
                    $this->arrays->getValue(
                        $this->templateVars,
                        $hash,
                        []
                    )
                );

                $value = str_replace(
                    $construction[ 0 ],
                    $replacedValue,
                    $value
                );
            }
        }

        /** @var HashTranslateDirective $hashTransDirective */
        $hashTransDirective = ObjectManager::getInstance()->get(HashTranslateDirective::class);

        if (preg_match_all(
            $hashTransDirective->getRegularExpression(),
            $value,
            $constructions,
            PREG_SET_ORDER
        )) {
            foreach ($constructions as $construction) {
                $replacedValue = $hashTransDirective->process(
                    $construction,
                    $subject,
                    $this->arrays->getValue(
                        $this->templateVars,
                        $hash,
                        []
                    )
                );

                $value = str_replace(
                    $construction[ 0 ],
                    $replacedValue,
                    $value
                );
            }
        }

        return [$value];
    }

    public function afterVarDirective(
        \Magento\Email\Model\Template\Filter $subject,
        ?string $result,
        array $construction = []
    ): ?string {
        $value = $this->arrays->getValue(
                $construction,
                '2',
                ''
            ) . $this->arrays->getValue(
                $construction,
                'filters',
                ''
            );

        $parts = explode(
            '|',
            $value,
            2
        );

        if (2 === count($parts)) {
            $modifier = $this->arrays->getValue(
                $parts,
                '1'
            );

            if ($modifier === 'filter') {
                $result = $subject->filter($result);
            }
        }

        return $result;
    }
}
