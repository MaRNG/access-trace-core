<?php

declare(strict_types=1);

namespace App\Model\Database\Repository;

use App\Model\Log\Dto\LogEntryDto;
use Nette\Database\Explorer;

final readonly class LogEntryRepository
{
    private const TABLE = 'log_entry';

    public function __construct(
        private Explorer $database
    )
    {
    }

    public function insert(int $accessLogId, LogEntryDto $entry): void
    {
        $this->database->query(
            'INSERT OR IGNORE INTO ' . self::TABLE . ' (access_log_id, ip, datetime, method, path, query, status, referer, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $accessLogId,
            $entry->ip,
            $entry->datetime,
            $entry->method,
            $entry->path,
            $entry->query,
            $entry->status,
            $entry->referer,
            $entry->userAgent
        );
    }

    /**
     * @param LogEntryDto[] $entries
     */
    public function insertMany(int $accessLogId, array $entries): void
    {
        if (count($entries) === 0)
        {
            return;
        }

        // Chunk to avoid SQLite variable limit (default 999 or 32766)
        // 9 parameters per row. 100 rows = 900 params. Safe.
        $chunks = array_chunk($entries, 100);

        foreach ($chunks as $chunk)
        {
            $sql = 'INSERT OR IGNORE INTO ' . self::TABLE . ' (access_log_id, ip, datetime, method, path, query, status, referer, user_agent) VALUES ';
            $params = [];
            $values = [];

            foreach ($chunk as $entry)
            {
                $values[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?)';
                array_push(
                    $params,
                    $accessLogId,
                    $entry->ip,
                    $entry->datetime,
                    $entry->method,
                    $entry->path,
                    $entry->query,
                    $entry->status,
                    $entry->referer,
                    $entry->userAgent
                );
            }

            $sql .= implode(', ', $values);
            $this->database->query($sql, ...$params);
        }
    }

    public function findByIp(string $ip, ?int $projectId = null, ?int $accessLogId = null): array
    {
        $selection = $this->database->table(self::TABLE)
            ->where('ip', $ip)
            ->order('datetime ASC');

        if ($projectId !== null)
        {
            $selection->where('access_log.project_id', $projectId);
        }

        if ($accessLogId !== null)
        {
            $selection->where('access_log_id', $accessLogId);
        }

        return $selection->fetchAll();
    }

    public function findByTimeRange(\DateTimeInterface $from, \DateTimeInterface $to, ?int $projectId = null, ?int $accessLogId = null): array
    {
        $selection = $this->database->table(self::TABLE)
            ->where('datetime >= ?', $from)
            ->where('datetime <= ?', $to)
            ->order('datetime ASC');

        if ($projectId !== null)
        {
            $selection->where('access_log.project_id', $projectId);
        }

        if ($accessLogId !== null)
        {
            $selection->where('access_log_id', $accessLogId);
        }

        return $selection->fetchAll();
    }
}
