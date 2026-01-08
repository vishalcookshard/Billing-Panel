<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplateSeeder extends Seeder
{
    public function run()
    {
        $templates = [
            [
                'name' => 'invoice_created',
                'subject' => 'New Invoice Created',
                'body' => 'Dear {{user_name}}, your invoice #{{invoice_id}} has been created.',
                'variables' => json_encode(['user_name', 'invoice_id']),
            ],
            [
                'name' => 'payment_received',
                'subject' => 'Payment Received',
                'body' => 'Thank you {{user_name}}, your payment for invoice #{{invoice_id}} was received.',
                'variables' => json_encode(['user_name', 'invoice_id']),
            ],
            [
                'name' => 'payment_failed',
                'subject' => 'Payment Failed',
                'body' => 'Dear {{user_name}}, your payment for invoice #{{invoice_id}} failed.',
                'variables' => json_encode(['user_name', 'invoice_id']),
            ],
            [
                'name' => 'service_created',
                'subject' => 'Service Activated',
                'body' => 'Your service {{service_name}} is now active. Login: {{login_url}}',
                'variables' => json_encode(['service_name', 'login_url']),
            ],
            [
                'name' => 'service_suspended',
                'subject' => 'Service Suspended',
                'body' => 'Your service {{service_name}} has been suspended.',
                'variables' => json_encode(['service_name']),
            ],
            [
                'name' => 'service_terminated',
                'subject' => 'Service Terminated',
                'body' => 'Your service {{service_name}} has been terminated.',
                'variables' => json_encode(['service_name']),
            ],
            [
                'name' => 'payment_reminder_3days',
                'subject' => 'Payment Reminder: 3 Days Left',
                'body' => 'Your invoice #{{invoice_id}} is due in 3 days.',
                'variables' => json_encode(['invoice_id']),
            ],
            [
                'name' => 'payment_reminder_1day',
                'subject' => 'Payment Reminder: 1 Day Left',
                'body' => 'Your invoice #{{invoice_id}} is due tomorrow.',
                'variables' => json_encode(['invoice_id']),
            ],
            [
                'name' => 'payment_overdue',
                'subject' => 'Payment Overdue',
                'body' => 'Your invoice #{{invoice_id}} is overdue.',
                'variables' => json_encode(['invoice_id']),
            ],
            [
                'name' => 'ticket_created',
                'subject' => 'Support Ticket Created',
                'body' => 'Your ticket #{{ticket_id}} has been created.',
                'variables' => json_encode(['ticket_id']),
            ],
            [
                'name' => 'ticket_reply_staff',
                'subject' => 'Staff Replied to Ticket',
                'body' => 'Staff replied to your ticket #{{ticket_id}}.',
                'variables' => json_encode(['ticket_id']),
            ],
            [
                'name' => 'ticket_reply_customer',
                'subject' => 'Customer Replied to Ticket',
                'body' => 'Customer replied to ticket #{{ticket_id}}.',
                'variables' => json_encode(['ticket_id']),
            ],
            [
                'name' => 'ticket_closed',
                'subject' => 'Ticket Closed',
                'body' => 'Your ticket #{{ticket_id}} has been closed.',
                'variables' => json_encode(['ticket_id']),
            ],
            [
                'name' => 'welcome_email',
                'subject' => 'Welcome to Our Service',
                'body' => 'Welcome {{user_name}}! Thank you for joining.',
                'variables' => json_encode(['user_name']),
            ],
            [
                'name' => 'password_reset',
                'subject' => 'Password Reset Request',
                'body' => 'Click here to reset your password: {{reset_link}}',
                'variables' => json_encode(['reset_link']),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('email_templates')->insert($template);
        }
    }
}
