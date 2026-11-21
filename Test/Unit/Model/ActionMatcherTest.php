<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Test\Unit\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StorvixLabs\CacheInsight\Model\ActionMatcher;

class ActionMatcherTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function cases(): array
    {
        return [
            'exact name' => ['checkout_cart_index', 'checkout_cart_index', true],
            'wildcard' => ['customer_account_login', 'customer_*', true],
            'one of several, comma' => ['cms_index_index', 'catalog_*,cms_index_index', true],
            'separated by spaces and semicolons' => ['contact_index_post', 'a_b_c ; contact_index_post', true],
            'no match' => ['catalog_product_view', 'customer_*', false],
            'prefix is not enough' => ['customer_account_login', 'customer', false],
            'empty pattern list' => ['catalog_product_view', '', false],
            'only separators' => ['catalog_product_view', ' , ; ', false],
        ];
    }

    #[DataProvider('cases')]
    public function testMatches(string $action, string $patterns, bool $expected): void
    {
        $this->assertSame($expected, ActionMatcher::matches($action, $patterns));
    }
}
