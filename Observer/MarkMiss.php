<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use StorvixLabs\CacheInsight\Model\RequestState;

/**
 * A controller only runs when the page was not served from the cache.
 */
class MarkMiss implements ObserverInterface
{
    public function __construct(
        private readonly RequestState $state
    ) {
    }

    public function execute(Observer $observer): void
    {
        $this->state->controllerRan = true;
    }
}
