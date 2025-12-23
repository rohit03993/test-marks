<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessQueuedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:process {--tries=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all queued jobs once and exit (like npm run build)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing queued jobs...');
        
        // Process all jobs and stop when queue is empty
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => $this->option('tries'),
        ]);

        $this->info('All queued jobs processed!');
        return 0;
    }
}
