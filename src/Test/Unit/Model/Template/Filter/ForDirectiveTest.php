<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Test\Unit\Model\Template\Filter;

use ArrayIterator;
use Infrangible\CmsFilter\Model\Template\Filter\ForDirective;
use Infrangible\CmsFilter\Model\Template\Filter\ForIfDirective;
use Magento\Framework\DataObject;
use Magento\Framework\Filter\Template;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests the loops {{for}} with conditions {{fif}}.
 *
 * The filter returns the content it filters unchanged, so that the placeholders of {{fif}} are replaced by the loop.
 */
#[AllowMockObjectsWithoutExpectations]
class ForDirectiveTest extends TestCase
{
    /**
     * Tests a loop over arrays and data objects with an index, a condition and a placeholder that is not set.
     *
     * @return void
     */
    public function testProcess(): void
    {
        $this->assertSame(
            '[0 Alpha open {kept}][1 Beta closed {kept}]',
            $this->process(
                '{{for store in stores}}[{{var loop.index}} {{var store.name}} ' .
                '{{fif store.open}}open{{fielse}}closed{{/fif}} {kept}{{var store.missing}}]{{/for}}',
                [
                    'stores' => [
                        ['name' => 'Alpha', 'open' => 1],
                        new DataObject(['name' => 'Beta', 'open' => 0]),
                        'not an entry'
                    ]
                ]
            )
        );
    }

    /**
     * Tests a loop over a Traversable list, which Magento's loop rejects with a type error.
     *
     * @return void
     */
    public function testProcessTraversable(): void
    {
        $this->assertSame(
            'A;B;',
            $this->process(
                '{{for product in products}}{{var product.sku}};{{/for}}',
                ['products' => new ArrayIterator([new DataObject(['sku' => 'A']), new DataObject(['sku' => 'B'])])]
            )
        );
    }

    /**
     * Tests that the content of entries without placeholders is kept; Magento's loop returns nothing.
     *
     * @return void
     */
    public function testProcessWithoutPlaceholders(): void
    {
        $this->assertSame(
            '<hr/><hr/>',
            $this->process('{{for store in stores}}<hr/>{{/for}}', ['stores' => [['a' => 1], ['a' => 2]]])
        );
    }

    /**
     * Tests that loops over values that are not lists are kept unchanged.
     *
     * @return void
     */
    public function testProcessInvalidList(): void
    {
        $this->assertSame(
            '{{for store in name}}{{var store.name}}{{/for}}',
            $this->process('{{for store in name}}{{var store.name}}{{/for}}', ['name' => 'Alpha'])
        );
    }

    /**
     * Tests that the entry and the loop are set as variables of the filter, which filters the content of {{fif}}.
     *
     * @return void
     */
    public function testProcessSetsFilterVariables(): void
    {
        $filter = $this->createMock(Template::class);
        $directive = new ForDirective(new ArrayResolver(), new ForIfDirective(new ArrayResolver()));

        $filter->expects($this->once())
            ->method('setVariables')
            ->with(
                $this->callback(
                    static fn(array $variables): bool => $variables[ 'store' ] instanceof DataObject &&
                        $variables[ 'store' ]->getData('name') === 'Alpha' &&
                        $variables[ 'loop' ] instanceof DataObject &&
                        $variables[ 'loop' ]->getData('index') === 0
                )
            );

        $text = '{{for store in stores}}{{var store.name}}{{/for}}';

        $this->assertSame(1, preg_match($directive->getRegularExpression(), $text, $construction));
        $this->assertSame('Alpha', $directive->process($construction, $filter, ['stores' => [['name' => 'Alpha']]]));
    }

    /**
     * Returns the processed loop of the text.
     *
     * @param string $text
     * @param array<string, mixed> $templateVariables
     *
     * @return string
     */
    private function process(string $text, array $templateVariables): string
    {
        $directive = new ForDirective(new ArrayResolver(), new ForIfDirective(new ArrayResolver()));
        $filter = $this->createStub(Template::class);

        $filter->method('filter')->willReturnArgument(0);

        $this->assertSame(1, preg_match($directive->getRegularExpression(), $text, $construction));

        return $directive->process($construction, $filter, $templateVariables);
    }
}
