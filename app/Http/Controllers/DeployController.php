<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeployController extends Controller
{
    public function gitPull(Request $request)
    {
        $secret = env('DEPLOY_WEBHOOK_SECRET');

        if ($secret) {
            $signature = $request->header('X-Hub-Signature-256');
            if (!$signature) {
                Log::warning('Deploy webhook: missing signature', ['ip' => $request->ip()]);
                abort(403);
            }

            $payload = $request->getContent();
            $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

            if (!hash_equals($expected, $signature)) {
                Log::warning('Deploy webhook: invalid signature', ['ip' => $request->ip()]);
                abort(403);
            }
        }

        $branch = 'main';
        $triggerFile = storage_path('app/deploy-trigger');

        file_put_contents($triggerFile, $branch);

        Log::info("Deploy webhook: trigger written for {$branch}");

        return response()->json(['status' => 'ok', 'branch' => $branch]);
    }
}
