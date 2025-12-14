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
}
