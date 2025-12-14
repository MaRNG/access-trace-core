<?php

declare(strict_types=1);

namespace App\Model\Facade;

use App\Model\Database\Repository\ProjectRepository;

final readonly class ProjectFacade
{
    public function __construct(
        private ProjectRepository $projectRepository
    )
    {
    }

    public function getAll(): array
    {
        return array_map(fn($row) => $row->toArray(), $this->projectRepository->findAll());
    }

    public function getById(int $id): ?array
    {
        $row = $this->projectRepository->getById($id);
        return $row ? $row->toArray() : null;
    }
}
