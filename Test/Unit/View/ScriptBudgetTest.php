<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Unit\View;

use Iranimij\OpenFilter\ViewModel\Slider;
use PHPUnit\Framework\TestCase;

/**
 * The AJAX component (10 FE10, 02 §10): one static inline script (no PHP inside, so its CSP hash is the same on
 * every page and full page cache entry), registered for CSP, at most 6 KB gzipped, without eval-like constructs.
 */
class ScriptBudgetTest extends TestCase
{
    private const FILE = __DIR__ . '/../../../view/frontend/templates/js/openfilter.phtml';

    private const EXTRAS = __DIR__ . '/../../../view/frontend/templates/js/extras.phtml';

    private const MORE = __DIR__ . '/../../../view/frontend/templates/js/more.phtml';

    private function script(string $file = self::FILE): string
    {
        $template = (string) file_get_contents($file);
        self::assertSame(1, preg_match_all('#<script>(.*?)</script>#s', $template, $scripts), 'exactly one script');
        self::assertStringContainsString('</script>' . "\n" . '<?php $hyvaCsp->registerInlineScript() ?>', $template);

        // What shoppers get: PHP comments inside the script are not printed (they document the code at no cost).
        return (string) preg_replace('#[ \t]*<\?php /\*.*?\*/ \?>\n?#s', '', $scripts[1][0]);
    }

    public function testScriptIsStaticAndCspFriendly(): void
    {
        $script = $this->script();

        self::assertStringNotContainsString('<?', $script, 'no PHP inside the script but comments');
        self::assertDoesNotMatchRegularExpression('/\beval\s*\(|new Function|setTimeout\(\s*[\'"]|innerHTML\s*=/', $script);
    }

    public function testSliderPlaceholderMatchesTheCoreViewModel(): void
    {
        self::assertStringContainsString("'" . Slider::PLACEHOLDER . "'", $this->script());
    }

    public function testStaysWithinTheBudget(): void
    {
        self::assertLessThanOrEqual(6144, strlen((string) gzencode($this->script(), 9)));
    }

    public function testThe11ExtrasAreStaticCspFriendlyAndSmall(): void
    {
        $extras = $this->script(self::EXTRAS);

        self::assertStringNotContainsString('<?', $extras);
        self::assertDoesNotMatchRegularExpression('/\beval\s*\(|new Function|setTimeout\(\s*[\'"]|innerHTML\s*=/', $extras);
        // Four modes share the script (desktop apply, dropdown, horizontal bar, H1 template): each well under the
        // plan's 1.5 KB per mode, 2 KB together; it loads only where a store view uses one of them.
        self::assertLessThanOrEqual(2048, strlen((string) gzencode($extras, 9)), 'the 1.1 extras stay under 2 KB');
        self::assertStringContainsString('window.openFilterStage', $extras, 'the hook the main component calls');
        self::assertStringContainsString('window.openFilterStage', $this->script(), 'the main component calls it');
    }

    public function testThe12MoreProductsScriptIsStaticCspFriendlyAndSmall(): void
    {
        $more = $this->script(self::MORE);

        self::assertStringNotContainsString('<?', $more, 'the mode and texts come from #of-more-config');
        self::assertDoesNotMatchRegularExpression('/\beval\s*\(|new Function|setTimeout\(\s*[\'"]|innerHTML\s*=/', $more);
        self::assertLessThanOrEqual(1536, strlen((string) gzencode($more, 9)), '"More products" stays under 1.5 KB');
        self::assertStringContainsString('run: runScripts', $this->script(), 'the main component lends its script runner');
    }
}
