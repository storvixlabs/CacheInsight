<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use StorvixLabs\CacheInsight\Model\Config;
use StorvixLabs\CacheInsight\Model\Outcome;
use StorvixLabs\CacheInsight\Model\Report\Reader;

class Report implements ArgumentInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $cache = [];

    public function __construct(
        private readonly RequestInterface $request,
        private readonly Config $config,
        private readonly Reader $reader
    ) {
    }

    public function getDays(): int
    {
        return min(90, max(1, (int)$this->request->getParam('days', 7)));
    }

    public function getFrom(): string
    {
        return gmdate('Y-m-d', time() - ($this->getDays() - 1) * 86400);
    }

    public function getTo(): string
    {
        return gmdate('Y-m-d');
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function isPageCacheEnabled(): bool
    {
        return $this->config->isPageCacheEnabled();
    }

    public function canCountHits(): bool
    {
        return $this->config->canCountHits() && $this->config->getHitSamplePercent() > 0;
    }

    /**
     * @return array{hits: int, misses: int, rate: float|null, by_outcome: array<string, int>}
     */
    public function getSummary(): array
    {
        if (!isset($this->cache['summary'])) {
            $totals = $this->reader->getTotals($this->getFrom(), $this->getTo());
            $hits = $totals[Outcome::HIT] ?? 0;
            unset($totals[Outcome::HIT]);
            $misses = array_sum($totals);
            $this->cache['summary'] = [
                'hits' => $hits,
                'misses' => $misses,
                'rate' => $this->canCountHits() && ($hits + $misses) > 0 ? $hits / ($hits + $misses) * 100 : null,
                'by_outcome' => $totals,
            ];
        }

        return $this->cache['summary'];
    }

    /**
     * @return array<int, array{page_type: string, total: int, outcomes: array<string, int>}>
     */
    public function getPageTypes(): array
    {
        return $this->cache['pages'] ??= $this->reader->getPageTypes($this->getFrom(), $this->getTo());
    }

    /**
     * @return array<int, array{name: string, requests: int, pages: array<string, int>}>
     */
    public function getCauses(string $kind): array
    {
        return $this->cache['causes' . $kind] ??= $this->reader->getCauses($kind, $this->getFrom(), $this->getTo());
    }

    /**
     * @return array<string, array{label: string, advice: string}>
     */
    public function getOutcomes(): array
    {
        return Outcome::describe();
    }
}
