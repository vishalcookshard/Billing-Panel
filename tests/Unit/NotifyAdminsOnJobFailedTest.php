<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminJobFailedNotification;
use App\Listeners\NotifyAdminsOnJobFailed;
use App\Models\User;
use Illuminate\Queue\Events\JobFailed;

class NotifyAdminsOnJobFailedTest extends TestCase
{
    public function test_admins_are_notified_on_job_failed_event()
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $job = new class {
            public function getName() { return 'TestJob'; }
            public function payload() { return ['foo' => 'bar']; }
        };

        $event = new JobFailed('redis', $job, new \Exception('boom'));

        $listener = new NotifyAdminsOnJobFailed();
        $listener->handle($event);

        Notification::assertSentTo([$admin], AdminJobFailedNotification::class);
    }
}
