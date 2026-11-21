<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Turns one finished storefront request into counter updates. Called when the response is ready to be sent,
 * so the layout has been rendered and the cache headers are final.
 */
class Recorder
{
    public const HIT_PAGE_TYPE = '(served from cache)';

    private const MAX_COOKIES = 15;

    public function __construct(
        private readonly Config $config,
        private readonly RequestState $state,
        private readonly Storage $storage,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function record(RequestInterface $request, ResponseInterface $response): void
    {
        if (!$this->config->isEnabled() || !$response instanceof HttpResponse
            || !method_exists($request, 'isGet') || !$request->isGet() || $request->isAjax()
        ) {
            return;
        }
        $day = gmdate('Y-m-d');
        $storeId = (int)$this->storeManager->getStore()->getId();

        if (!$this->state->controllerRan) {
            $this->recordHit($response, $day, $storeId);

            return;
        }
        $page = (string)$request->getFullActionName();
        if ($page === '' || $this->config->isExcluded($page)) {
            return;
        }
        $setCookies = $this->setCookieNames($response);
        $outcome = Outcome::classify(
            (int)$response->getHttpResponseCode(),
            $this->state->layoutCacheable,
            $this->cacheControl($response),
            $setCookies,
            !$this->config->canCountHits()
        );
        $causes = [];
        foreach ($this->state->uncacheableBlocks as $block) {
            $causes[] = [$day, 'block', $this->clip($block), $page, 1];
        }
        foreach (array_slice($this->requestCookieNames($request), 0, self::MAX_COOKIES) as $cookie) {
            $causes[] = [$day, 'cookie', $this->clip((string)$cookie), $page, 1];
        }
        foreach (array_slice($setCookies, 0, self::MAX_COOKIES) as $cookie) {
            $causes[] = [$day, 'set_cookie', $this->clip($cookie), $page, 1];
        }

        $this->storage->addDaily([[$day, $page, $outcome, $storeId, 1]]);
        $this->storage->addCauses($causes);
    }

    private function recordHit(HttpResponse $response, string $day, int $storeId): void
    {
        $percent = $this->config->getHitSamplePercent();
        if (!$this->config->canCountHits() || $percent === 0 || (int)$response->getHttpResponseCode() !== 200
            || random_int(1, 100) > $percent
        ) {
            return;
        }
        // Each sampled hit stands for 100 / percent real hits.
        $this->storage->addDaily([[$day, self::HIT_PAGE_TYPE, Outcome::HIT, $storeId, (int)round(100 / $percent)]]);
    }

    /**
     * @return string[]
     */
    private function requestCookieNames(RequestInterface $request): array
    {
        $header = method_exists($request, 'getHeader') ? $request->getHeader('Cookie') : '';
        $names = [];
        foreach (explode(';', is_string($header) ? $header : '') as $pair) {
            $name = trim((string)strstr($pair, '=', true));
            if ($name !== '') {
                $names[$name] = $name;
            }
        }

        return array_values($names);
    }

    /**
     * @return string[]
     */
    private function setCookieNames(HttpResponse $response): array
    {
        $names = [];
        $headers = $response->getHeaders()->get('Set-Cookie');
        foreach ($headers === false ? [] : (is_iterable($headers) ? $headers : [$headers]) as $header) {
            $value = method_exists($header, 'getName') ? $header->getName() : strstr($header->getFieldValue(), '=', true);
            if (is_string($value) && $value !== '') {
                $names[$value] = $value;
            }
        }
        // PHP sessions send their cookie with the native header() function, not through the response object.
        foreach (headers_list() as $line) {
            if (stripos($line, 'Set-Cookie:') === 0) {
                $name = trim((string)strstr(substr($line, 11), '=', true));
                if ($name !== '') {
                    $names[$name] = $name;
                }
            }
        }

        return array_values($names);
    }

    private function cacheControl(HttpResponse $response): string
    {
        $header = $response->getHeader('Cache-Control');

        return $header ? (string)$header->getFieldValue() : '';
    }

    private function clip(string $value): string
    {
        return mb_substr($value, 0, 190);
    }
}
