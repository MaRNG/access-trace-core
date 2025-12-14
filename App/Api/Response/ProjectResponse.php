<?php

declare(strict_types=1);

namespace App\Api\Response;

final readonly class ProjectResponse
{
    public function __construct(
        public int $id,
        public string $name
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name']
        );
    }
}
