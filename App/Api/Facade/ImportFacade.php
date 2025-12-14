<?php

declare(strict_types=1);

namespace App\Api\Facade;

use App\Model\Database\Repository\AccessLogRepository;

final readonly class ImportFacade
{
    public function __construct(
        private AccessLogRepository $accessLogRepository
    )
    {
    }

    public function getAll(): array
    {
        return array_map(fn($row) => $row->toArray(), $this->accessLogRepository->findAll());
    }
}
