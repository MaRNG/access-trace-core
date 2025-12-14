<?php

declare(strict_types=1);

namespace App\Api\Response;

final readonly class ImportResponse
{
    public function __construct(
        public int $id,
        public int $project_id,
        public string $filename,
        public \DateTimeInterface $imported_at,
        public ?\DateTimeInterface $from_time,
        public ?\DateTimeInterface $to_time,
        public int $entries_total,
        public int $file_lines_count,
        public int $lines_processed,
        public int $is_processed
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            project_id: $data['project_id'],
            filename: $data['filename'],
            imported_at: $data['imported_at'],
            from_time: $data['from_time'],
            to_time: $data['to_time'],
            entries_total: $data['entries_total'],
            file_lines_count: $data['file_lines_count'],
            lines_processed: $data['lines_processed'],
            is_processed: $data['is_processed']
        );
    }
}
