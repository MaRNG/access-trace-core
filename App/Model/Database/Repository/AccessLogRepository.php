<?php

declare(strict_types=1);

namespace App\Model\Database\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

class AccessLogRepository
{
	private const TABLE = 'access_log';

	public function __construct(
		private Explorer $database
	) {
	}

	public function create(int $projectId, string $filename): ActiveRow
	{
		return $this->database->table(self::TABLE)->insert([
			'project_id' => $projectId,
			'filename' => $filename,
			'imported_at' => new \DateTimeImmutable(),
			'entries_total' => 0,
		]);
	}

	public function updateStats(int $id, int $total, ?\DateTimeInterface $from, ?\DateTimeInterface $to): void
	{
		$this->database->table(self::TABLE)
			->where('id', $id)
			->update([
				'entries_total' => $total,
				'from_time' => $from,
				'to_time' => $to,
			]);
	}
}
