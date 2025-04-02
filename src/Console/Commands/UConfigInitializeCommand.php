<?php

namespace Ultra\UltraConfigManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;
use Ultra\UltraConfigManager\Facades\UConfig;

class UConfigInitializeCommand extends Command
{
    protected $signature = 'uconfig:initialize';
    protected $description = 'Initial setup and DB registration for UltraConfigManager';

    public function handle()
    {
        if (!Schema::hasTable('uconfig')) {
            $this->error("❌ Table 'uconfig' not found. Run migrations first.");
            return self::FAILURE;
        }

        try {
            $shown = UConfig::get('initial_publication_message', null);
            if ($shown === null || $shown == 0) {
                UConfig::set('initial_publication_message', '0', 'system');

                $output = new ConsoleOutput();
                $output->writeln('<info>Note: aliases.php already exists. Add the following line:</info>');
                $output->writeln("'UConfig' => Ultra\\UltraConfigManager\\Facades\\UConfig::class,");
                $output->writeln('<info>See documentation under "Facades: UConfig" for more.</info>');

                UConfig::set('initial_publication_message', '1', 'system');
                Log::info('Initial publication message displayed');
            }

            $this->info("✅ UConfig initialization complete.");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("🔥 Failed to initialize UConfig: {$e->getMessage()}");
            Log::error("UConfig initialization failed", ['exception' => $e]);
            return self::FAILURE;
        }
    }
}
