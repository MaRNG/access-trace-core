<?php

declare(strict_types=1);

namespace App\Model\Log\Dto;

final readonly class LogEntryDto
{
    public function __construct(
        public string             $ip,
        public \DateTimeImmutable $datetime,
        public string             $method,
        public string             $path,
        public ?string            $query,
        public int                $status,
        public ?string            $referer,
        public ?string            $userAgent
    )
    {
    }
}
