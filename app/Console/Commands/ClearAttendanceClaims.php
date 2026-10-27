<?php

namespace App\Console\Commands;

use App\Models\AttendanceClaim;
use Illuminate\Console\Command;

class ClearAttendanceClaims extends Command
{
    protected $signature = 'claims:clear';

    protected $description = 'Delete all attendance claims (runs daily at midnight)';

    public function handle(): int
    {
        $count = AttendanceClaim::query()->delete();

        $this->info("Deleted {$count} attendance claim(s).");

        return self::SUCCESS;
    }
}
