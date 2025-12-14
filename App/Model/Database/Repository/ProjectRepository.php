<?php

declare(strict_types=1);

namespace App\Model\Database\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

class ProjectRepository
{
	private const TABLE = 'project';

	public function __construct(
		private Explorer $database
	) {
	}

	public function getByName(string $name): ?ActiveRow
	{
		return $this->database->table(self::TABLE)
			->where('name', $name)
			->fetch();
	}

	public function create(string $name): ActiveRow
	{
		return $this->database->table(self::TABLE)->insert([
			'name' => $name,
		]);
	}

	public function getOrCreate(string $name): ActiveRow
	{
		$project = $this->getByName($name);
		if (!$project) {
			$project = $this->create($name);
		}
		return $project;
	}
}
