<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $templateName;
    public $userId;
    public $variables;

    public function __construct($templateName, $userId, $variables)
    {
        $this->templateName = $templateName;
        $this->userId = $userId;
        $this->variables = $variables;
    }

    public function handle(EmailService $service)
    {
        $user = User::find($this->userId);
        if (!$user) return;
        try {
            $service->send($this->templateName, $user, $this->variables);
        } catch (\Exception $e) {
            Log::error('SendEmailJob failed', ['user_id' => $this->userId, 'error' => $e->getMessage()]);
            $this->fail($e);
        }
    }
}
