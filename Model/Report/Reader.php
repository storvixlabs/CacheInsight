<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Model\Report;

use Magento\Framework\App\ResourceConnection;
use StorvixLabs\CacheInsight\Model\Outcome;
use StorvixLabs\CacheInsight\Model\Storage;

class Reader
{
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Requests by outcome for the period.
     *
     * @return array<string, int>
     */
    public function getTotals(string $from, string $to): array
    {
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchPairs(
            $connection->select()
                ->from($this->resource->getTableName(Storage::DAILY), ['outcome', 'total' => 'SUM(requests)'])
                ->where('day >= ?', $from)
                ->where('day <= ?', $to)
                ->group('outcome')
        );

        return array_map('intval', $rows);
    }

    /**
     * Misses per page type with the split by outcome, busiest first.
     *
     * @return array<int, array{page_type: string, total: int, outcomes: array<string, int>}>
     */
    public function getPageTypes(string $from, string $to, int $limit = 30): array
    {
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(
                    $this->resource->getTableName(Storage::DAILY),
                    ['page_type', 'outcome', 'requests' => 'SUM(requests)']
                )
                ->where('day >= ?', $from)
                ->where('day <= ?', $to)
                ->where('outcome <> ?', Outcome::HIT)
                ->group(['page_type', 'outcome'])
        );
        $types = [];
        foreach ($rows as $row) {
            $type = &$types[$row['page_type']];
            $type['page_type'] = $row['page_type'];
            $type['outcomes'][$row['outcome']] = (int)$row['requests'];
            $type['total'] = ($type['total'] ?? 0) + (int)$row['requests'];
            unset($type);
        }
        usort($types, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return array_slice($types, 0, $limit);
    }

    /**
     * Most frequent causes of one kind (block, cookie, set_cookie) with the page types they were seen on.
     *
     * @return array<int, array{name: string, requests: int, pages: array<string, int>}>
     */
    public function getCauses(string $kind, string $from, string $to, int $limit = 15): array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(Storage::CAUSE);
        $top = $connection->fetchPairs(
            $connection->select()
                ->from($table, ['name', 'total' => 'SUM(requests)'])
                ->where('kind = ?', $kind)
                ->where('day >= ?', $from)
                ->where('day <= ?', $to)
                ->group('name')
                ->order('total DESC')
                ->limit($limit)
        );
        if ($top === []) {
            return [];
        }
        $pages = [];
        foreach ($connection->fetchAll(
            $connection->select()
                ->from($table, ['name', 'page_type', 'requests' => 'SUM(requests)'])
                ->where('kind = ?', $kind)
                ->where('day >= ?', $from)
                ->where('day <= ?', $to)
                ->where('name IN (?)', array_keys($top))
                ->group(['name', 'page_type'])
                ->order('requests DESC')
        ) as $row) {
            $pages[$row['name']][$row['page_type']] = (int)$row['requests'];
        }
        $result = [];
        foreach ($top as $name => $total) {
            $result[] = ['name' => (string)$name, 'requests' => (int)$total, 'pages' => array_slice($pages[$name] ?? [], 0, 4, true)];
        }

        return $result;
    }
}
