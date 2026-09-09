<?php

namespace App\Console\Commands;

use App\Services\TaskHandlerService;
use Illuminate\Console\Command;

class TaskHandlerWebhook extends Command
{
    protected $signature = 'task:update
                            {taskId : Task UUID}
                            {--started : Mark task as started (in_progress)}
                            {--completed : Mark task as completed (done)}
                            {--blocked= : Mark task as blocked with reason}
                            {--progress= : Update progress (0-100)}
                            {--status= : Set status (in_progress|review|done|blocked)}
                            {--remark= : Add remark text}
                            {--agent= : Agent identifier}';
    protected $description = 'Send webhook update to Task Handler for a task';

    public function handle(TaskHandlerService $th): int
    {
        if (! $th->isConfigured()) {
            $this->error('Task Handler not configured. Set TASK_HANDLER_PROJECT_ID and TASK_HANDLER_WEBHOOK_TOKEN in .env');
            return self::FAILURE;
        }

        $taskId = $this->argument('taskId');
        $agent = $this->option('agent') ?? 'simpang-homestay';

        try {
            if ($this->option('started')) {
                $result = $th->taskStarted($taskId, $agent);
                $this->info("Task {$taskId} → in_progress");
            } elseif ($this->option('completed')) {
                $result = $th->taskCompleted($taskId, $this->option('remark'), $agent);
                $this->info("Task {$taskId} → done");
            } elseif ($this->option('blocked')) {
                $result = $th->taskBlocked($taskId, $this->option('blocked'), $agent);
                $this->info("Task {$taskId} → blocked: " . $this->option('blocked'));
            } elseif ($this->option('progress') || $this->option('status') || $this->option('remark')) {
                $result = $th->taskProgress(
                    $taskId,
                    (int) ($this->option('progress') ?? 0),
                    $this->option('status') ?? 'in_progress',
                    $this->option('remark'),
                    $agent
                );
                $this->info("Task {$taskId} → updated");
            } else {
                $this->error('Specify --started, --completed, --blocked, or --progress/--status');
                return self::FAILURE;
            }

            if (! empty($result['remarkId'])) {
                $this->info("Remark ID: {$result['remarkId']}");
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
