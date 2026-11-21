<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Test\Unit\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StorvixLabs\CacheInsight\Model\ActionMatcher;
use StorvixLabs\CacheInsight\Model\Outcome;

class OutcomeTest extends TestCase
{
    /**
     * @return array<string, array{0: int, 1: bool, 2: string, 3: string[], 4: string}>
     */
    public static function responses(): array
    {
        return [
            'plain cold page' => [200, true, 'public, max-age=86400, s-maxage=86400', [], Outcome::CACHEABLE_MISS],
            'layout block' => [200, false, 'no-store, no-cache, must-revalidate', ['PHPSESSID'], Outcome::UNCACHEABLE_BLOCK],
            'private by code' => [200, true, 'max-age=0, must-revalidate, no-cache, no-store', [], Outcome::PRIVATE_RESPONSE],
            'session started' => [200, true, 'public, max-age=86400', ['phpsessid'], Outcome::SESSION_COOKIE],
            'redirect' => [301, true, '', [], Outcome::REDIRECT],
            'not found' => [404, true, 'public', [], Outcome::ERROR],
        ];
    }

    /**
     * @param string[] $cookies
     */
    #[DataProvider('responses')]
    public function testClassify(int $status, bool $cacheable, string $control, array $cookies, string $expected): void
    {
        $this->assertSame($expected, Outcome::classify($status, $cacheable, $control, $cookies));
    }

    public function testBuiltInCacheIgnoresHeadersAndCookies(): void
    {
        $this->assertSame(
            Outcome::CACHEABLE_MISS,
            Outcome::classify(200, true, 'max-age=0, no-store', ['PHPSESSID'], false)
        );
        $this->assertSame(Outcome::UNCACHEABLE_BLOCK, Outcome::classify(200, false, '', [], false));
    }

    public function testActionMatcher(): void
    {
        $patterns = 'customer_*, checkout_cart_index ,contact_index_post';

        $this->assertTrue(ActionMatcher::matches('customer_account_login', $patterns));
        $this->assertTrue(ActionMatcher::matches('checkout_cart_index', $patterns));
        $this->assertFalse(ActionMatcher::matches('catalog_product_view', $patterns));
        $this->assertFalse(ActionMatcher::matches('catalog_product_view', ''));
    }
}
