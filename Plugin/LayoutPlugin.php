<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Plugin;

use Magento\Framework\View\Layout;
use StorvixLabs\CacheInsight\Model\Config;
use StorvixLabs\CacheInsight\Model\RequestState;

/**
 * After a page is rendered, notes which blocks (cacheable="false") keep it out of the cache.
 */
class LayoutPlugin
{
    private const MAX_BLOCKS = 30;

    public function __construct(
        private readonly Config $config,
        private readonly RequestState $state
    ) {
    }

    /**
     * @param string $result
     * @return string
     */
    public function afterGetOutput(Layout $subject, $result)
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }
        try {
            if (!$subject->isCacheable()) {
                $this->state->layoutCacheable = false;
                foreach (($subject->getNode() ?: new \SimpleXMLElement('<layout/>'))->xpath('//block[@cacheable="false"]') ?: [] as $element) {
                    $name = $element->getBlockName();
                    if ($name !== false && $subject->hasElement($name)
                        && count($this->state->uncacheableBlocks) < self::MAX_BLOCKS
                    ) {
                        $this->state->uncacheableBlocks[$name] = $name;
                    }
                }
            }
        } catch (\Throwable) {
            // Diagnostics must never break page rendering.
        }

        return $result;
    }
}
