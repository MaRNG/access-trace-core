<?php

declare(strict_types=1);

namespace App\Model\Log;

use App\Model\Database\Repository\AccessLogRepository;
use App\Model\Database\Repository\LogEntryRepository;
use App\Model\Database\Repository\ProjectRepository;
use App\Model\Log\Dto\LogEntryDto;
use Nette\Database\Explorer;
use Nette\Utils\Strings;

final readonly class LogImporter
{
	// Regex to parse Common Log Format / Combined Log Format
	// Matches: IP, Date, Method, URL, Status, Bytes (ignored), Referer, UA
	private const LOG_REGEX = '/^(\S+) \S+ \S+ \[([^\]]+)\] "([A-Z]+) (.+?) HTTP\/[0-9.]+" (\d+) \d+ "([^"]*)" "([^"]*)"/';

	public function __construct(
		private Explorer $database,
		private ProjectRepository $projectRepository,
		private AccessLogRepository $accessLogRepository,
		private LogEntryRepository $logEntryRepository
	) {
	}

	/**
	 * @param string $pathPattern Glob pattern for files
	 * @param string $projectName
	 * @param bool $dryRun
	 * @return \Generator<string> Yields status messages
	 */
	public function import(string $pathPattern, string $projectName, bool $dryRun = false): \Generator
	{
		$files = glob($pathPattern);

		if ($files === false || count($files) === 0) {
			yield "No files found matching pattern: $pathPattern";
			return;
		}

		// Ensure project exists
		$project = $this->projectRepository->getOrCreate($projectName);
		yield "Project: {$project->name} (ID: {$project->id})";

		foreach ($files as $file) {
			if (!is_file($file) || !is_readable($file)) {
				yield "Skipping unreadable file: $file";
				continue;
			}

			yield "Processing file: $file";
			$this->processFile($file, $project->id, $dryRun);
		}
	}

	private function processFile(string $filePath, int $projectId, bool $dryRun): void
	{
		$handle = fopen($filePath, 'r');
		if (!$handle) {
			return;
		}

		$this->database->beginTransaction();

		try {
			// Create Access Log record
			$accessLog = $this->accessLogRepository->create($projectId, basename($filePath));
			$accessLogId = $accessLog->id;

			$count = 0;
			$firstTime = null;
			$lastTime = null;

			while (($line = fgets($handle)) !== false) {
				$entry = $this->parseLine($line);

				if ($entry) {
					if (!$dryRun) {
						$this->logEntryRepository->insert($accessLogId, $entry);
					}

					// Update stats
					$count++;
					if ($firstTime === null || $entry->datetime < $firstTime) {
						$firstTime = $entry->datetime;
					}
					if ($lastTime === null || $entry->datetime > $lastTime) {
						$lastTime = $entry->datetime;
					}
				}
			}

			if (!$dryRun) {
				$this->accessLogRepository->updateStats($accessLogId, $count, $firstTime, $lastTime);
				$this->database->commit();
			} else {
				$this->database->rollBack(); // Rollback in dry-run to clean up access_log entry
			}

		} catch (\Throwable $e) {
			$this->database->rollBack();
			throw $e;
		} finally {
			fclose($handle);
		}
	}

	private function parseLine(string $line): ?LogEntryDto
	{
		$match = Strings::match($line, self::LOG_REGEX);

		if (!$match) {
			return null;
		}

		// $match indices:
		// 1: IP
		// 2: Date [30/May/2025:15:40:26 +0200]
		// 3: Method
		// 4: Full Path (URI)
		// 5: Status
		// 6: Referer
		// 7: User Agent

		$fullUrl = $match[4];
		$parsedUrl = parse_url($fullUrl);
		$path = $parsedUrl['path'] ?? '';
		$query = $parsedUrl['query'] ?? null;

		// Filter: Only .php or no extension
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		if ($extension !== '' && $extension !== 'php') {
			return null; // Skip assets (css, js, images, etc.)
		}

		// Parse Date
		$date = \DateTimeImmutable::createFromFormat('d/M/Y:H:i:s O', $match[2]);
		if (!$date) {
			return null;
		}

		return new LogEntryDto(
			ip: $match[1],
			datetime: $date,
			method: $match[3],
			path: $path,
			query: $query,
			status: (int)$match[5],
			referer: $match[6] === '-' ? null : $match[6],
			userAgent: $match[7]
		);
	}
}
