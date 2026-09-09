<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaskHandlerService
{
    protected string $baseUrl;
    protected ?string $projectId = null;
    protected ?string $webhookToken = null;

    public function __construct()
    {
        $this->baseUrl = config('task-handler.base_url', 'http://fekusa.id');
        $this->projectId = config('task-handler.project_id');
        $this->webhookToken = config('task-handler.webhook_token');
    }

    public function isConfigured(): bool
    {
        return !empty($this->projectId) && !empty($this->webhookToken);
    }

    /**
     * Sync PRD content to Task Handler. Auto-creates tasks if AI is configured.
     */
    public function syncPrd(string $content): array
    {
        $response = Http::acceptJson()
            ->withToken($this->webhookToken)
            ->post("{$this->baseUrl}/api/v1/dev-projects/{$this->projectId}/prd", [
                'content' => $content,
                'sync' => true,
            ]);

        if ($response->failed()) {
            Log::error('TaskHandler: PRD sync failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('TaskHandler PRD sync failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get tasks list, optionally filtered by status.
     */
    public function getTasks(?string $status = null): array
    {
        $url = "{$this->baseUrl}/api/v1/dev-projects/{$this->projectId}/tasks";
        if ($status) {
            $url .= '?status=' . $status;
        }

        $response = Http::acceptJson()->get($url);

        if ($response->failed()) {
            Log::error('TaskHandler: get tasks failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('TaskHandler get tasks failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Send webhook update for a task.
     */
    public function webhook(string $action, string $taskId, array $data = [], string $agentIdentifier = 'simpang-homestay'): array
    {
        $payload = array_merge([
            'action' => $action,
            'taskId' => $taskId,
            'agent' => ['identifier' => $agentIdentifier],
        ], $data ? ['data' => $data] : []);

        $response = Http::acceptJson()
            ->withToken($this->webhookToken)
            ->post("{$this->baseUrl}/api/v1/webhook/{$this->projectId}", $payload);

        if ($response->failed()) {
            Log::error('TaskHandler: webhook failed', [
                'action' => $action,
                'taskId' => $taskId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('TaskHandler webhook failed: ' . $response->body());
        }

        return $response->json();
    }

    public function taskStarted(string $taskId, string $agentIdentifier = 'simpang-homestay'): array
    {
        return $this->webhook('task_started', $taskId, [
            'status' => 'in_progress',
        ], $agentIdentifier);
    }

    public function taskProgress(string $taskId, int $progress, string $status = 'in_progress', ?string $remark = null, string $agentIdentifier = 'simpang-homestay'): array
    {
        $data = [
            'progress' => $progress,
            'status' => $status,
        ];
        if ($remark) {
            $data['remark'] = $remark;
            $data['remarkType'] = 'progress';
        }
        return $this->webhook('task_progress', $taskId, $data, $agentIdentifier);
    }

    public function taskCompleted(string $taskId, ?string $remark = null, string $agentIdentifier = 'simpang-homestay'): array
    {
        $data = [
            'progress' => 100,
            'status' => 'done',
        ];
        if ($remark) {
            $data['remark'] = $remark;
            $data['remarkType'] = 'completion';
        }
        return $this->webhook('task_completed', $taskId, $data, $agentIdentifier);
    }

    public function taskBlocked(string $taskId, string $remark, string $agentIdentifier = 'simpang-homestay'): array
    {
        return $this->webhook('task_blocked', $taskId, [
            'status' => 'blocked',
            'remark' => $remark,
            'remarkType' => 'blocker',
        ], $agentIdentifier);
    }

    public function addRemark(string $taskId, string $remark, string $remarkType = 'note', string $agentIdentifier = 'simpang-homestay'): array
    {
        return $this->webhook('remark_added', $taskId, [
            'remark' => $remark,
            'remarkType' => $remarkType,
        ], $agentIdentifier);
    }
}
