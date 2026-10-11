<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Courses\Services\LiveRecordingService;

class PublishLiveRecordings extends Command
{
    protected $signature = 'app:publish-live-recordings';
    protected $description = 'Publishes as course lessons the recordings of live sessions that have already ended';

    public function handle(LiveRecordingService $recordings): int
    {
        $this->info('Grabaciones publicadas como lección: ' . $recordings->publishPending());

        return self::SUCCESS;
    }
}
