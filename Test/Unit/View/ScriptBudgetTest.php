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

    private function script(): string
    {
        $template = (string) file_get_contents(self::FILE);
        self::assertSame(1, preg_match_all('#<script>(.*?)</script>#s', $template, $scripts), 'exactly one script');
        self::assertStringContainsString('</script>' . "\n" . '<?php $hyvaCsp->registerInlineScript() ?>', $template);

        return $scripts[1][0];
    }

    public function testScriptIsStaticAndCspFriendly(): void
    {
        $script = $this->script();

        self::assertStringNotContainsString('<?', $script, 'no PHP inside the script');
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
}
