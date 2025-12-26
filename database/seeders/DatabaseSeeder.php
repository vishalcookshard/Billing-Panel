<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Page;
use App\Models\ServiceCategory;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Currency;
use App\Models\PromoCode;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        // Create default pages
        $pages = [
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'content' => '<h1>Privacy Policy</h1><p>Your privacy policy here.</p>',
                'is_published' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms',
                'content' => '<h1>Terms of Service</h1><p>Your terms here.</p>',
                'is_published' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => '<h1>Frequently Asked Questions</h1><p>FAQ content here.</p>',
                'is_published' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(['slug' => $page['slug']], $page);
        }

        // Create sample service category and plans
        $category = ServiceCategory::firstOrCreate(
            ['slug' => 'vps-hosting'],
            [
                'name' => 'VPS Hosting',
                'description' => 'High-performance virtual private servers.',
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        if ($category->plans()->count() === 0) {
            $plans = [
                [
                    'name' => 'Basic VPS',
                    'slug' => 'basic-vps',
                    'description' => 'Entry-level VPS with 1 vCPU and 2GB RAM',
                    'price_monthly' => 5.99,
                    'is_active' => true,
                    'display_order' => 1,
                    'features' => json_encode(['1 vCPU', '2GB RAM', '50GB SSD', '1TB Bandwidth']),
                ],
                [
                    'name' => 'Standard VPS',
                    'slug' => 'standard-vps',
                    'description' => 'Standard VPS with 2 vCPU and 4GB RAM',
                    'price_monthly' => 11.99,
                    'is_active' => true,
                    'display_order' => 2,
                    'features' => json_encode(['2 vCPU', '4GB RAM', '100GB SSD', '2TB Bandwidth']),
                ],
                [
                    'name' => 'Premium VPS',
                    'slug' => 'premium-vps',
                    'description' => 'Premium VPS with 4 vCPU and 8GB RAM',
                    'price_monthly' => 23.99,
                    'is_active' => true,
                    'display_order' => 3,
                    'features' => json_encode(['4 vCPU', '8GB RAM', '200GB SSD', '4TB Bandwidth']),
                ],
            ];

            foreach ($plans as $plan) {
                $plan['service_category_id'] = $category->id;
                Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
            }
        }

        // Default currency
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'rate_to_usd' => 1]);

        // Example promo code
        PromoCode::firstOrCreate(['code' => 'WELCOME10'], [
            'type' => 'percentage',
            'value' => 10,
            'active' => true,
        ]);
    }
}
