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
        $this->database->table(self::TABLE)->insert([
                                                        'access_log_id' => $accessLogId,
                                                        'ip' => $entry->ip,
                                                        'datetime' => $entry->datetime,
                                                        'method' => $entry->method,
                                                        'path' => $entry->path,
                                                        'query' => $entry->query,
                                                        'status' => $entry->status,
                                                        'referer' => $entry->referer,
                                                        'user_agent' => $entry->userAgent,
                                                    ]);
    }

    /**
     * @param LogEntryDto[] $entries
     */
    public function insertMany(int $accessLogId, array $entries): void
    {
        $data = [];
        foreach ($entries as $entry)
        {
            $data[] = [
                'access_log_id' => $accessLogId,
                'ip' => $entry->ip,
                'datetime' => $entry->datetime,
                'method' => $entry->method,
                'path' => $entry->path,
                'query' => $entry->query,
                'status' => $entry->status,
                'referer' => $entry->referer,
                'user_agent' => $entry->userAgent,
            ];
        }

        if (count($data) > 0)
        {
            $this->database->table(self::TABLE)->insert($data);
        }
    }

    public function findByIp(string $ip, ?int $projectId = null, ?int $accessLogId = null): array
    {
        $selection = $this->database->table(self::TABLE)
            ->where('ip', $ip)
            ->order('datetime DESC');

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
            ->order('datetime DESC');

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
