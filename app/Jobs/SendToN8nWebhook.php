<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendToN8nWebhook implements ShouldQueue
{
    use Queueable;

    public array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = config('services.n8n.webhook_url');

        if (!$url) {
            Log::warning('N8N Webhook URL not configured. Skipping webhook delivery.');
            return;
        }

        try {
            $response = Http::post($url, $this->data);

            if ($response->failed()) {
                Log::error('N8N Webhook delivery failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('N8N Webhook delivery exception', [
                'message' => $e->getMessage()
            ]);
        }
    }
}
