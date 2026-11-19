<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Test\Unit\Model\Template\Filter;

use Infrangible\CmsFilter\Model\Template\Filter\HashTranslateDirective;
use Infrangible\CmsFilter\Model\Template\Filter\HashVarDirective;
use Magento\Framework\Filter\DirectiveProcessor\Filter\FilterApplier;
use Magento\Framework\Filter\Template;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the directives #var# and #trans#.
 */
class HashDirectivesTest extends TestCase
{
    /**
     * Returns texts and the variable and filters of #var# in them, null if the text has no directive.
     *
     * @return array<string, array{0: string, 1: array{0: string, 1: string}|null}>
     */
    public static function getVarTexts(): array
    {
        return [
            'variable'    => ['lat="#var latitude#"', [' latitude', '']],
            'path'        => ['#var store.name#', [' store.name', '']],
            'filters'     => ['#var name|escape|nl2br#', [' name', '|escape|nl2br']],
            'anchor'      => [
                '<a href="https://example.com/#variants">V</a> <a href="https://example.com/#top">T</a>',
                null
            ],
            'no variable' => ['#var#', null]
        ];
    }

    /**
     * Tests which texts contain the directive #var#.
     *
     * @param string $text
     * @param array{0: string, 1: string}|null $expected
     *
     * @return void
     * @dataProvider getVarTexts
     */
    #[DataProvider('getVarTexts')]
    public function testVarRegularExpression(string $text, ?array $expected): void
    {
        $directive = new HashVarDirective(new ArrayResolver(), $this->createStub(FilterApplier::class));

        $found = preg_match($directive->getRegularExpression(), $text, $construction);

        $this->assertSame(
            $expected,
            $found === 1 ? [$construction[ 2 ], $construction[ 'filters' ] ?? ''] : null
        );
    }

    /**
     * Tests the value of #var#.
     *
     * @return void
     */
    public function testVarProcess(): void
    {
        $directive = new HashVarDirective(new ArrayResolver(), $this->createStub(FilterApplier::class));

        $this->assertSame(1, preg_match($directive->getRegularExpression(), '#var store.name#', $construction));
        $this->assertSame(
            'Alpha & Beta',
            $directive->process(
                $construction,
                $this->createStub(Template::class),
                ['store' => ['name' => 'Alpha & Beta']]
            )
        );
    }

    /**
     * Returns texts and the text of #trans# in them, null if the text has no directive.
     *
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function getTranslateTexts(): array
    {
        return [
            'text'        => ['<span>#trans "Opening Hours"#:</span>', 'Opening Hours'],
            'other words' => ['#transfer "x"# and "y"#', null],
            'no quotes'   => ['#trans Address#', null]
        ];
    }

    /**
     * Tests which texts contain the directive #trans# and its result.
     *
     * @param string $text
     * @param string|null $expected
     *
     * @return void
     * @dataProvider getTranslateTexts
     */
    #[DataProvider('getTranslateTexts')]
    public function testTranslate(string $text, ?string $expected): void
    {
        $directive = new HashTranslateDirective();

        if (preg_match($directive->getRegularExpression(), $text, $construction) !== 1) {
            $this->assertNull($expected);

            return;
        }

        $this->assertSame($expected, $directive->process($construction, $this->createStub(Template::class), []));
    }
}
