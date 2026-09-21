<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RecordingRecoveryService;

class RecoverRecordingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recordings:recover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover interrupted recordings (queued/processing) and dispatch to queue again.';

    /**
     * Execute the console command.
     */
    public function handle(RecordingRecoveryService $recoveryService)
    {
        $this->info('Starting recording recovery...');

        $count = $recoveryService->recoverInterruptedRecordings();

        $this->info("Successfully recovered {$count} recordings.");

        return Command::SUCCESS;
    }
}
