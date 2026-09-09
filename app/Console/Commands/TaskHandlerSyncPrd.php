<?php

namespace App\Console\Commands;

use App\Services\TaskHandlerService;
use Illuminate\Console\Command;

class TaskHandlerSyncPrd extends Command
{
    protected $signature = 'task:sync-prd {file : Path to PRD markdown file}';
    protected $description = 'Sync PRD content to Task Handler and auto-generate tasks';

    public function handle(TaskHandlerService $th): int
    {
        if (! $th->isConfigured()) {
            $this->error('Task Handler not configured. Set TASK_HANDLER_PROJECT_ID and TASK_HANDLER_WEBHOOK_TOKEN in .env');
            return self::FAILURE;
        }

        $file = $this->argument('file');
        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $content = file_get_contents($file);
        $this->info("Syncing PRD from: {$file}");

        try {
            $result = $th->syncPrd($content);
            $this->info('PRD synced: ' . ($result['prd']['title'] ?? 'OK'));
            $this->info("Tasks created: " . ($result['tasksCreated'] ?? 0));

            if (! empty($result['tasks'])) {
                $this->table(['Title', 'Status', 'ID'], array_map(
                    fn($t) => [$t['title'] ?? '-', $t['status'] ?? '-', $t['id'] ?? '-'],
                    $result['tasks']
                ));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
