<?php

declare(strict_types=1);

namespace App\Model\Database\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

final readonly class AccessLogRepository
{
	private const TABLE = 'access_log';

	public function __construct(
		private Explorer $database
	) {
	}

	public function create(int $projectId, string $filename, int $fileLinesCount): ActiveRow
	{
		return $this->database->table(self::TABLE)->insert([
			'project_id' => $projectId,
			'filename' => $filename,
			'imported_at' => new \DateTimeImmutable(),
			'entries_total' => 0,
			'file_lines_count' => $fileLinesCount,
			'lines_processed' => 0,
			'is_processed' => 0,
		]);
	}

	public function updateStats(int $id, int $entriesTotal, int $linesProcessed, ?\DateTimeInterface $from, ?\DateTimeInterface $to): void
	{
		$this->database->table(self::TABLE)
			->where('id', $id)
			->update([
				'entries_total' => $entriesTotal,
				'lines_processed' => $linesProcessed,
				'from_time' => $from,
				'to_time' => $to,
			]);
	}

	public function markAsProcessed(int $id): void
	{
		$this->database->table(self::TABLE)
			->where('id', $id)
			->update([
				'is_processed' => 1,
			]);
	}

    public function findAll(): array
    {
        return $this->database->table(self::TABLE)->order('imported_at DESC')->fetchAll();
    }
}
