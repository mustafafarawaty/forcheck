<?php

namespace App\Console\Commands;

use App\Services\LiveSession\LiveSessionRoomService;
use Illuminate\Console\Command;

/**
 * Remove stored recordings after their expiry time.
 */
class CleanupExpiredSessionRecordings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:cleanup-recordings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete live session recordings that expired after 3 hours';

    public function __construct(
        private readonly LiveSessionRoomService $roomService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = $this->roomService->cleanupExpiredRecordings();
        $this->info("Deleted {$deleted} expired recordings.");

        return self::SUCCESS;
    }
}
