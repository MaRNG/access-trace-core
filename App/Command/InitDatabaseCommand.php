<?php

declare(strict_types=1);

namespace App\Command;

use Nette\Database\Explorer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'logs:init-db', description: 'Initialize SQLite database schema for logs')]
class InitDatabaseCommand extends Command
{
	public function __construct(
		private Explorer $database
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('Initializing database schema...');

		$this->database->beginTransaction();

		try {
			// Project table
			$this->database->query('
				CREATE TABLE IF NOT EXISTS project (
					id INTEGER PRIMARY KEY AUTOINCREMENT,
					name TEXT NOT NULL UNIQUE
				)
			');

			// Access Log table
			$this->database->query('
				CREATE TABLE IF NOT EXISTS access_log (
					id INTEGER PRIMARY KEY AUTOINCREMENT,
					project_id INTEGER NOT NULL,
					filename TEXT NOT NULL,
					imported_at DATETIME NOT NULL,
					from_time DATETIME,
					to_time DATETIME,
					entries_total INTEGER DEFAULT 0,
					FOREIGN KEY (project_id) REFERENCES project(id)
				)
			');

			// Log Entry table
			$this->database->query('
				CREATE TABLE IF NOT EXISTS log_entry (
					id INTEGER PRIMARY KEY AUTOINCREMENT,
					access_log_id INTEGER NOT NULL,
					ip TEXT,
					datetime DATETIME,
					method TEXT,
					path TEXT,
					query TEXT,
					status INTEGER,
					referer TEXT,
					user_agent TEXT,
					FOREIGN KEY (access_log_id) REFERENCES access_log(id)
				)
			');

			// Indexes for performance
			$this->database->query('CREATE INDEX IF NOT EXISTS idx_log_entry_access_log_id ON log_entry (access_log_id)');
			$this->database->query('CREATE INDEX IF NOT EXISTS idx_access_log_project_id ON access_log (project_id)');
			$this->database->query('CREATE INDEX IF NOT EXISTS idx_log_entry_ip ON log_entry (ip)');
			$this->database->query('CREATE INDEX IF NOT EXISTS idx_log_entry_datetime ON log_entry (datetime)');

			$this->database->commit();
			$output->writeln('<info>Database initialized successfully.</info>');
			return Command::SUCCESS;

		} catch (\Throwable $e) {
			$this->database->rollBack();
			$output->writeln('<error>Error initializing database: ' . $e->getMessage() . '</error>');
			return Command::FAILURE;
		}
	}
}
