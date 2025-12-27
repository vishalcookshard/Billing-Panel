<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateEnvironment extends Command
{
    protected $signature = 'env:validate';
    protected $description = 'Validate required environment variables';

    public function handle()
    {
        $required = [
            'APP_KEY' => 'Application encryption key',
            'DB_PASSWORD' => 'Database password (production)',
            'MAIL_MAILER' => 'Mail configuration',
        ];

        $errors = [];

        foreach ($required as $key => $description) {
            $value = env($key);
            if (empty($value)) {
                $errors[] = "$key is not set ($description)";
            }
            // Special validation for DB_PASSWORD in production
            if ($key === 'DB_PASSWORD' && env('APP_ENV') === 'production' && strlen($value) < 16) {
                $errors[] = "DB_PASSWORD must be at least 16 characters in production";
            }
        }

        if (!empty($errors)) {
            $this->error('Environment validation failed:');
            foreach ($errors as $error) {
                $this->error('  - ' . $error);
            }
            return 1;
        }

        $this->info('✓ Environment configuration is valid');
        return 0;
    }
}
