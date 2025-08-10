<?php

namespace Farayaz\LaravelSpy\Commands;

use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Carbon;

class CleanCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spy:clean
                            {--days= : Delete logs older than specified days}
                            {--url= : Delete logs matching the specified URL pattern}
                            {--force : (optional) Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean HTTP logs based on days or URL pattern';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::SUCCESS;
        }

        $days = (int) ($this->option('days') ?? config('spy.clean_days'));
        $url = $this->option('url');

        $query = HttpLog::query();

        if ($days > 0) {
            $query->where('created_at', '<', Carbon::now()->subDays($days));
        }

        if ($url) {
            $query->where('url', 'LIKE', '%' . addcslashes($url, '%_') . '%');
        }

        $deletedCount = $query->delete();

        $this->info("Successfully deleted {$deletedCount} log(s).");

        return self::SUCCESS;
    }
}
