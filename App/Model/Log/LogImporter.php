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
    private const BATCH_SIZE = 100;

    // Regex to parse Common Log Format / Combined Log Format
    // Matches: IP, Date, Method, URL, Status, Bytes (ignored), Referer, UA
    private const LOG_REGEX = '/^(\S+) \S+ \S+ \[([^\]]+)\] "([A-Z]+) (.+?) HTTP\/[0-9.]+" (\d+) \d+ "([^"]*)" "([^"]*)"/';

    public function __construct(
        private Explorer            $database,
        private ProjectRepository   $projectRepository,
        private AccessLogRepository $accessLogRepository,
        private LogEntryRepository  $logEntryRepository
    )
    {
    }

    /**
     * @param string $pathPattern Glob pattern for files
     * @param string $projectName
     * @param bool $dryRun
     * @param callable(int $current, int $total, string $file, ?float $eta): void|null $onProgress
     */
    public function import(string $pathPattern, string $projectName, bool $dryRun = false, ?callable $onProgress = null): void
    {
        $files = glob($pathPattern);

        if ($files === false || count($files) === 0)
        {
            return;
        }

        // Ensure project exists
        $project = $this->projectRepository->getOrCreate($projectName);

        foreach ($files as $file)
        {
            if (!is_file($file) || !is_readable($file))
            {
                continue;
            }

            $this->processFile($file, $project->id, $dryRun, $onProgress);
        }
    }

    /**
     * @param callable(int $current, int $total, string $file, ?float $eta): void|null $onProgress
     */
    private function processFile(string $filePath, int $projectId, bool $dryRun, ?callable $onProgress): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle)
        {
            return;
        }

        $totalLines = $this->countLines($filePath);
        $processedLines = 0;
        $startTime = microtime(true);

        if ($onProgress)
        {
            $onProgress(0, $totalLines, $filePath, null);
        }

        try
        {
            $accessLogId = null;
            if (!$dryRun)
            {
                // Create Access Log record
                $accessLog = $this->accessLogRepository->create($projectId, basename($filePath));
                $accessLogId = $accessLog->id;
            }

            $count = 0;
            $firstTime = null;
            $lastTime = null;
            $buffer = [];

            while (($line = fgets($handle)) !== false)
            {
                $processedLines++;
                if ($onProgress && ($processedLines % 100 === 0 || $processedLines === $totalLines))
                {
                    $eta = null;
                    $elapsed = microtime(true) - $startTime;
                    if ($elapsed > 0 && $processedLines > 0)
                    {
                        $rate = $processedLines / $elapsed;
                        $remaining = $totalLines - $processedLines;
                        $eta = $remaining / $rate;
                    }
                    $onProgress($processedLines, $totalLines, $filePath, $eta);
                }

                $entry = $this->parseLine($line);

                if ($entry)
                {
                    // Update stats
                    $count++;
                    if ($firstTime === null || $entry->datetime < $firstTime)
                    {
                        $firstTime = $entry->datetime;
                    }
                    if ($lastTime === null || $entry->datetime > $lastTime)
                    {
                        $lastTime = $entry->datetime;
                    }

                    if (!$dryRun)
                    {
                        $buffer[] = $entry;
                        if (count($buffer) >= self::BATCH_SIZE)
                        {
                            $this->database->beginTransaction();
                            try
                            {
                                $this->logEntryRepository->insertMany($accessLogId, $buffer);
                                $this->accessLogRepository->updateStats($accessLogId, $count, $firstTime, $lastTime);
                                $this->database->commit();
                            } catch (\Throwable $e)
                            {
                                $this->database->rollBack();
                                throw $e;
                            }
                            $buffer = [];
                        }
                    }
                }
            }

            if (!$dryRun)
            {
                if (count($buffer) > 0)
                {
                    $this->database->beginTransaction();
                    try
                    {
                        $this->logEntryRepository->insertMany($accessLogId, $buffer);
                        $this->accessLogRepository->updateStats($accessLogId, $count, $firstTime, $lastTime);
                        $this->database->commit();
                    } catch (\Throwable $e)
                    {
                        $this->database->rollBack();
                        throw $e;
                    }
                }
                $this->accessLogRepository->markAsProcessed($accessLogId);
            }

        } catch (\Throwable $e)
        {
            throw $e;
        } finally
        {
            fclose($handle);
        }
    }

    private function countLines(string $filePath): int
    {
        $handle = fopen($filePath, 'r');
        if (!$handle)
        {
            return 0;
        }

        $lines = 0;
        $lastChar = "\n";

        while (!feof($handle))
        {
            $chunk = fread($handle, 8192);
            $lines += substr_count($chunk, "\n");
            if ($chunk !== '')
            {
                $lastChar = substr($chunk, -1);
            }
        }

        if ($lastChar !== "\n")
        {
            $lines++;
        }

        fclose($handle);
        return $lines;
    }

    private function parseLine(string $line): ?LogEntryDto
    {
        $match = Strings::match($line, self::LOG_REGEX);

        if (!$match)
        {
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
        if ($extension !== '' && $extension !== 'php')
        {
            return null; // Skip assets (css, js, images, etc.)
        }

        // Parse Date
        $date = \DateTimeImmutable::createFromFormat('d/M/Y:H:i:s O', $match[2]);
        if (!$date)
        {
            return null;
        }

        return new LogEntryDto(
            ip       : $match[1],
            datetime : $date,
            method   : $match[3],
            path     : $path,
            query    : $query,
            status   : (int)$match[5],
            referer  : $match[6] === '-' ? null : $match[6],
            userAgent: $match[7]
        );
    }
}
