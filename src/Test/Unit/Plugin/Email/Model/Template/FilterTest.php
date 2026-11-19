<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CmsFilter\Test\Unit\Plugin\Email\Model\Template;

use Infrangible\CmsFilter\Model\Template\Filter\HashTranslateDirective;
use Infrangible\CmsFilter\Model\Template\Filter\HashVarDirective;
use Infrangible\CmsFilter\Plugin\Email\Model\Template\Filter;
use Infrangible\CmsFilter\Test\Unit\Model\Template\Filter\ArrayResolver;
use Magento\Email\Model\Template\Filter as EmailFilter;
use Magento\Framework\Filter\DirectiveProcessor\Filter\FilterApplier;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests the directives #var# and #trans# and the modifier "filter" in Magento's template filters.
 */
#[AllowMockObjectsWithoutExpectations]
class FilterTest extends TestCase
{
    /**
     * Tests that #var# and #trans# are replaced with the variables of the filter.
     *
     * @return void
     */
    public function testBeforeFilter(): void
    {
        $plugin = $this->getPlugin();
        $filter = $this->createStub(EmailFilter::class);
        $otherFilter = $this->createStub(EmailFilter::class);

        $plugin->afterSetVariables($filter, $filter, ['name' => 'Alpha']);
        $plugin->afterSetVariables($filter, $filter, ['city' => 'Jena']);
        $plugin->afterSetVariables($otherFilter, $otherFilter, ['name' => 'Beta']);

        $this->assertSame(
            ['{{widget title="Alpha, Jena"}} Address <a href="https://example.com/#variants">V</a>#'],
            $plugin->beforeFilter(
                $filter,
                '{{widget title="#var name#, #var city#"}} #trans "Address"# ' .
                '<a href="https://example.com/#variants">V</a>#'
            )
        );

        $this->assertSame(['Beta'], $plugin->beforeFilter($otherFilter, '#var name#'));
        $this->assertSame([''], $plugin->beforeFilter($filter, null));
    }

    /**
     * Tests that the value of {{var}} is filtered again only with the modifier "filter".
     *
     * @return void
     */
    public function testAfterVarDirective(): void
    {
        $plugin = $this->getPlugin();
        $filter = $this->createMock(EmailFilter::class);

        $filter->expects($this->once())->method('filter')->with('{{var title}}')->willReturn('Stores');

        $this->assertSame(
            'Stores',
            $plugin->afterVarDirective($filter, '{{var title}}', ['{{var content|filter}}', 'var', ' content|filter'])
        );
        $this->assertSame(
            '{{var title}}',
            $plugin->afterVarDirective($filter, '{{var title}}', ['{{var content}}', 'var', ' content'])
        );
        $this->assertNull($plugin->afterVarDirective($filter, null, ['{{var content|filter}}']));
    }

    /**
     * Returns the plugin with the directives.
     *
     * @return Filter
     */
    private function getPlugin(): Filter
    {
        return new Filter(
            new HashVarDirective(new ArrayResolver(), $this->createStub(FilterApplier::class)),
            new HashTranslateDirective()
        );
    }
}
