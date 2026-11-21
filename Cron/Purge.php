<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Cron;

use StorvixLabs\CacheInsight\Model\Config;
use StorvixLabs\CacheInsight\Model\Storage;

class Purge
{
    public function __construct(
        private readonly Config $config,
        private readonly Storage $storage
    ) {
    }

    public function execute(): void
    {
        $this->storage->purgeOlderThan($this->config->getRetentionDays());
    }
}
