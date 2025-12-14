<?php

declare(strict_types=1);

namespace App\Api\Response;

final readonly class LogEntryResponse
{
    public function __construct(
        public int $access_log_id,
        public ?string $ip,
        public \DateTimeInterface $datetime,
        public ?string $method,
        public ?string $path,
        public ?string $query,
        public ?int $status,
        public ?string $referer = null,
        public ?string $user_agent = null
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            access_log_id: $data['access_log_id'],
            ip: $data['ip'] ?? null,
            datetime: $data['datetime'],
            method: $data['method'] ?? null,
            path: $data['path'] ?? null,
            query: $data['query'] ?? null,
            status: $data['status'] ?? null,
            referer: $data['referer'] ?? null,
            user_agent: $data['user_agent'] ?? null
        );
    }
}
