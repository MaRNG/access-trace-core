<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Log\LogImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'logs:import', description: 'Import access logs into database')]
class ImportLogsCommand extends Command
{
    public function __construct(
        private LogImporter $logImporter
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to log file(s) (supports wildcards)');
        $this->addOption('project', null, InputOption::VALUE_REQUIRED, 'Project name');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Parse without saving to database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $projectName = $input->getOption('project');
        $dryRun = (bool)$input->getOption('dry-run');

        if (!$projectName)
        {
            $output->writeln('<error>The --project option is required.</error>');
            return Command::FAILURE;
        }

        $output->writeln("Starting import for project '$projectName'...");
        if ($dryRun)
        {
            $output->writeln('<comment>DRY RUN: No changes will be saved.</comment>');
        }

        try
        {
            /** @var ProgressBar|null $progressBar */
            $progressBar = null;

            $this->logImporter->import(
                $path,
                $projectName,
                $dryRun,
                function (int $current, int $total, string $file, ?float $eta) use ($output, &$progressBar) {
                    if ($current === 0) {
                        if ($progressBar) {
                            $progressBar->finish();
                            $output->writeln('');
                        }
                        $output->writeln("Processing file: $file");
                        $progressBar = new ProgressBar($output, $total);
                        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% / ETA: %eta_val%');
                        $progressBar->setMessage('?', 'eta_val');
                        $progressBar->start();
                    }

                    if ($progressBar) {
                        $progressBar->setProgress($current);
                        if ($eta !== null) {
                            $progressBar->setMessage(gmdate("H:i:s", (int)$eta), 'eta_val');
                        }
                    }
                }
            );

            if ($progressBar) {
                $progressBar->finish();
                $output->writeln('');
            }

            $output->writeln('<info>Import completed.</info>');
            return Command::SUCCESS;

        } catch (\Throwable $e)
        {
            $output->writeln('<error>Error during import: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
