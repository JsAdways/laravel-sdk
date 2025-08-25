<?php

namespace Jsadways\LaravelSDK\Console\Commands;

use Illuminate\Console\Command;

class SetupArchitectureCommand extends Command
{
    protected $signature = 'setup:architecture';
    protected $description = 'Setup architecture for Laravel SDK';

    public function handle()
    {
        $result = $this->call('vendor:publish', ['--tag' => 'CLAUDE-docs']);

        $scriptPath = base_path('vendor/jsadways/laravel-sdk/src/setup-architecture.sh');

        if (!file_exists($scriptPath)) {
            $this->error('Setup script not found!');
            return Command::FAILURE;
        }

        $this->info('Setting up architecture...');

        $process = proc_open(
            "bash {$scriptPath}",
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (is_resource($process)) {
            fclose($pipes[0]);

            while ($line = fgets($pipes[1])) {
                $this->line(rtrim($line));
            }

            fclose($pipes[1]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            if ($returnCode === 0) {
                $this->info('Architecture setup completed successfully!');
                return Command::SUCCESS;
            } else {
                $this->error('Architecture setup failed!');
                return Command::FAILURE;
            }
        }

        $this->error('Failed to execute setup script!');
        return Command::FAILURE;
    }
}
