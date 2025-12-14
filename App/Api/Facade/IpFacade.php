<?php

declare(strict_types=1);

namespace App\Api\Facade;

use App\Model\Database\Repository\LogEntryRepository;

final readonly class IpFacade
{
    public function __construct(
        private LogEntryRepository $logEntryRepository
    )
    {
    }

    public function getActivity(string $ip, bool $detailed = false): array
    {
        $rows = $this->logEntryRepository->findByIp($ip);

        return array_map(function ($row) use ($detailed) {
            $data = [
                'access_log_id' => $row->access_log_id,
                'datetime' => $row->datetime,
                'method' => $row->method,
                'path' => $row->path,
                'query' => $row->query,
                'status' => $row->status,
            ];

            if ($detailed) {
                $data['referer'] = $row->referer;
                $data['user_agent'] = $row->user_agent;
            }

            return $data;
        }, $rows);
    }
}
