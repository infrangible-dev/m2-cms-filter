<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Test\Unit\Model\Template\Filter;

use Infrangible\CmsFilter\Model\Template\Filter\ForIfDirective;
use Magento\Framework\Filter\Template;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the conditions {{fif}} inside loops.
 *
 * The filter marks the content it filters with "filtered:".
 */
class ForIfDirectiveTest extends TestCase
{
    /**
     * Returns values of the condition and whether the condition is met.
     *
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function getValues(): array
    {
        return [
            'text'         => ['yes', true],
            'one'          => [1, true],
            'true'         => [true, true],
            'array'        => [['a'], true],
            'zero'         => [0, false],
            'zero string'  => ['0', false],
            'zero decimal' => ['0.00', false],
            'empty string' => ['', false],
            'false'        => [false, false],
            'null'         => [null, false],
            'empty array'  => [[], false]
        ];
    }

    /**
     * Tests the condition with and without else part.
     *
     * @param mixed $value
     * @param bool $expected
     *
     * @return void
     * @dataProvider getValues
     */
    #[DataProvider('getValues')]
    public function testProcess(mixed $value, bool $expected): void
    {
        $directive = new ForIfDirective(new ArrayResolver());
        $filter = $this->getFilter();
        $templateVariables = ['store' => ['flag' => $value]];

        $this->assertSame(
            $expected ? 'filtered:set' : 'filtered:empty',
            $directive->process(
                $this->getConstruction($directive, '{{fif store.flag}}set{{fielse}}empty{{/fif}}'),
                $filter,
                $templateVariables
            )
        );

        $this->assertSame(
            $expected ? 'filtered:set' : '',
            $directive->process(
                $this->getConstruction($directive, '{{fif store.flag}}set{{/fif}}'),
                $filter,
                $templateVariables
            )
        );
    }

    /**
     * Tests that the construction is kept without variables, like Magento's {{if}}.
     *
     * @return void
     */
    public function testProcessWithoutVariables(): void
    {
        $directive = new ForIfDirective(new ArrayResolver());

        $this->assertSame(
            '{{fif flag}}set{{/fif}}',
            $directive->process($this->getConstruction($directive, '{{fif flag}}set{{/fif}}'), $this->getFilter(), [])
        );
    }

    /**
     * Returns a filter that marks the filtered content.
     *
     * @return Template
     */
    private function getFilter(): Template
    {
        $filter = $this->createStub(Template::class);

        $filter->method('filter')->willReturnCallback(static fn(string $value): string => 'filtered:' . $value);

        return $filter;
    }

    /**
     * Returns the construction of the directive in the text.
     *
     * @param ForIfDirective $directive
     * @param string $text
     *
     * @return array<int|string, string>
     */
    private function getConstruction(ForIfDirective $directive, string $text): array
    {
        $this->assertSame(1, preg_match($directive->getRegularExpression(), $text, $construction));

        return $construction;
    }
}
