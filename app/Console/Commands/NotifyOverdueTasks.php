<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifyOverdueTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:notify-overdue {--dry-run : Do not send emails or update tasks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for overdue (not completed) tasks.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();

        $query = Task::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->where('status', '!=', TaskStatus::Completed->value)
            ->whereNull('overdue_notified_at')
            ->with('user')
            ->orderBy('id');

        $notifiedCount = 0;

        $query->chunkById(200, function ($tasks) use (&$notifiedCount) {
            foreach ($tasks as $task) {
                $notifiedCount++;

                if ($this->option('dry-run')) {
                    continue;
                }

                $task->user->notify(new TaskOverdueNotification($task));
                $task->forceFill(['overdue_notified_at' => now()])->save();
            }
        });

        $this->info("Overdue tasks notified: {$notifiedCount}");

        return self::SUCCESS;
    }
}
