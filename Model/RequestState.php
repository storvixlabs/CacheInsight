<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Model;

/**
 * Facts collected while one request is handled (shared object, one per request).
 */
class RequestState
{
    /**
     * A controller ran, so the page was not served from the cache.
     */
    public bool $controllerRan = false;

    public bool $layoutCacheable = true;

    /**
     * @var string[] Names of blocks that make the layout uncacheable
     */
    public array $uncacheableBlocks = [];
}
