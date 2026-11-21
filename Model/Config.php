<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\PageCache\Model\Config as PageCacheConfig;

class Config
{
    private const PATH = 'storvixlabs_cacheinsight/general/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly PageCacheConfig $pageCacheConfig
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH . 'enabled');
    }

    public function getHitSamplePercent(): int
    {
        return min(100, max(0, (int)$this->scopeConfig->getValue(self::PATH . 'hit_sample_percent')));
    }

    public function getRetentionDays(): int
    {
        return max(1, (int)$this->scopeConfig->getValue(self::PATH . 'retention_days'));
    }

    /**
     * True when PHP sees cache hits (built-in cache); with Varnish only misses reach PHP.
     */
    public function canCountHits(): bool
    {
        return $this->pageCacheConfig->isEnabled() && $this->pageCacheConfig->getType() === PageCacheConfig::BUILT_IN;
    }

    public function isPageCacheEnabled(): bool
    {
        return $this->pageCacheConfig->isEnabled();
    }

    public function isExcluded(string $actionName): bool
    {
        return ActionMatcher::matches($actionName, (string)$this->scopeConfig->getValue(self::PATH . 'excluded_actions'));
    }
}
