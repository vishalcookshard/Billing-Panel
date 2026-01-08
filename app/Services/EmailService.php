<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendEmailJob;

class EmailService
{
    public function send(string $templateName, User $user, array $variables): bool
    {
        $template = EmailTemplate::where('name', $templateName)->where('is_active', true)->first();
        if (!$template) return false;
        $body = $this->parseTemplate($template->body, $variables);
        Mail::to($user->email)->send(new \App\Mail\GenericMail($template->subject, $body));
        return true;
    }

    public function parseTemplate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    public function queueEmail(string $templateName, User $user, array $variables): void
    {
        SendEmailJob::dispatch($templateName, $user->id, $variables);
    }
}
