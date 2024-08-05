<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\CmsFilter\Plugin\Framework\Filter\DirectiveProcessor;

use FeWeDev\Base\Variables;
use Infrangible\CmsFilter\Model\Template\Filter\ForIfDirective;
use Magento\Framework\DataObject;
use Magento\Framework\Filter\Template;
use Magento\Framework\Filter\VariableResolverInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ForDirective
{
    /** @var VariableResolverInterface */
    protected $variableResolver;

    /** @var ForIfDirective */
    protected $forIfDirective;

    /** @var Variables */
    protected $variables;

    public function __construct(
        VariableResolverInterface $variableResolver,
        ForIfDirective $forIfDirective,
        Variables $variables)
    {
        $this->variableResolver = $variableResolver;
        $this->forIfDirective = $forIfDirective;
        $this->variables = $variables;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function aroundProcess(
        \Magento\Framework\Filter\DirectiveProcessor\ForDirective $subject,
        callable $proceed,
        array $construction,
        Template $filter,
        array $templateVariables): string
    {
        $result = $this->resolve($construction, $filter, $templateVariables);

        return str_replace(['{', '}'], '', $result);
    }

    private function resolve(array $construction, Template $filter, array $templateVariables): string
    {
        if (!$this->isValidLoop($construction)) {
            return $construction[0];
        }

        $loopData = $this->variableResolver->resolve($construction['loopData'], $filter, $templateVariables);

        $loopTextToReplace = $construction['loopBody'];
        $loopItemVariableName = preg_replace('/\s+/', '', $construction['loopItem']);

        if (is_array($loopData) || $loopData instanceof \Traversable) {
            return $this->getLoopReplacementText(
                $loopData, $loopItemVariableName, $loopTextToReplace, $filter, $templateVariables);
        }

        return $construction[0];
    }

    private function isValidLoop(array $construction): bool
    {
        $requiredFields = ['loopBody', 'loopItem', 'loopData'];

        $validFields = array_filter($requiredFields, function ($field) use ($construction) {
            return isset($construction[$field]) && strlen(trim($construction[$field]));
        });

        return count($requiredFields) == count($validFields);
    }

    private function getLoopReplacementText(
        array $loopData,
        string $loopItemVariableName,
        string $loopTextToReplace,
        Template $filter,
        array $templateVariables): string
    {
        $loopText = [];
        $loopIndex = 0;
        $loopDataObject = new DataObject();

        foreach ($loopData as $loopItemDataObject) {
            // Loop item can be an array or DataObject.
            // If loop item is an array, convert it to DataObject
            // to have unified interface if the collection
            if (!$loopItemDataObject instanceof DataObject) {
                if (!is_array($loopItemDataObject)) {
                    continue;
                }

                $loopItemDataObject = new DataObject($loopItemDataObject);
            }

            $loopDataObject->setData('index', $loopIndex++);
            $templateVariables['loop'] = $loopDataObject;
            $templateVariables[$loopItemVariableName] = $loopItemDataObject;

            $filter->setVariables(['loop' => $loopDataObject, $loopItemVariableName => $loopItemDataObject]);

            $loopTextReplaced = $loopTextToReplace;

            if (preg_match_all(
                $this->forIfDirective->getRegularExpression(), $loopTextReplaced, $constructions, PREG_SET_ORDER)) {

                foreach ($constructions as $construction) {
                    $replacedValue = $this->forIfDirective->process($construction, $filter, $templateVariables);

                    $loopTextReplaced = str_replace($construction[0], $replacedValue, $loopTextReplaced);
                }
            }

            // Current structure prohibits recursively calling template filter inside "for" directives
            if (preg_match_all(Template::CONSTRUCTION_PATTERN, $loopTextReplaced, $attributes, PREG_SET_ORDER)) {
                $subText = $loopTextReplaced;

                foreach ($attributes as $attribute) {
                    $text = $this->variableResolver->resolve($attribute[2], $filter, $templateVariables);

                    $subText = str_replace(
                        $attribute[0],
                        $text === null ? '' : (is_array($text) ? $text : $this->variables->stringValue($text)),
                        $subText);
                }

                $loopText[] = $subText;
            }

            unset($templateVariables[$loopItemVariableName]);
        }

        return implode('', $loopText);
    }
}
