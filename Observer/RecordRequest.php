<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use StorvixLabs\CacheInsight\Model\Config;
use StorvixLabs\CacheInsight\Model\Recorder;

/**
 * Runs for every storefront response, whether it came from the cache or was built by PHP.
 */
class RecordRequest implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Recorder $recorder,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }
        try {
            $this->recorder->record($observer->getEvent()->getRequest(), $observer->getEvent()->getResponse());
        } catch (\Throwable $e) {
            try {
                $this->logger->warning('Cache Insight could not record a request: ' . $e->getMessage());
            } catch (\Throwable) {
                // An unwritable log file must not break the storefront.
            }
        }
    }
}
