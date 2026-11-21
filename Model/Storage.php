<?php
declare(strict_types=1);

namespace StorvixLabs\CacheInsight\Model;

use Magento\Framework\App\ResourceConnection;

class Storage
{
    public const DAILY = 'storvixlabs_cacheinsight_daily';
    public const CAUSE = 'storvixlabs_cacheinsight_cause';

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * @param array<int, array{0: string, 1: string, 2: string, 3: int, 4: int}> $rows day, page_type, outcome, store_id, requests
     */
    public function addDaily(array $rows): void
    {
        $this->upsert(self::DAILY, ['day', 'page_type', 'outcome', 'store_id', 'requests'], $rows);
    }

    /**
     * @param array<int, array{0: string, 1: string, 2: string, 3: string, 4: int}> $rows day, kind, name, page_type, requests
     */
    public function addCauses(array $rows): void
    {
        $this->upsert(self::CAUSE, ['day', 'kind', 'name', 'page_type', 'requests'], $rows);
    }

    public function purgeOlderThan(int $days): void
    {
        $limit = gmdate('Y-m-d', time() - $days * 86400);
        $connection = $this->resource->getConnection();
        foreach ([self::DAILY, self::CAUSE] as $table) {
            $connection->delete($this->resource->getTableName($table), ['day < ?' => $limit]);
        }
    }

    public function truncate(): void
    {
        $connection = $this->resource->getConnection();
        foreach ([self::DAILY, self::CAUSE] as $table) {
            $connection->truncateTable($this->resource->getTableName($table));
        }
    }

    /**
     * @param string[] $columns
     * @param array<int, array<int, mixed>> $rows
     */
    private function upsert(string $table, array $columns, array $rows): void
    {
        if ($rows === []) {
            return;
        }
        $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE requests = requests + VALUES(requests)',
            $this->resource->getTableName($table),
            implode(',', $columns),
            implode(',', array_fill(0, count($rows), $placeholders))
        );
        $this->resource->getConnection()->query($sql, array_merge(...array_map('array_values', $rows)));
    }
}
