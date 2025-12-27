<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Page;

class XssPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_xss_payload_is_escaped_in_blade()
    {
        $page = Page::create([
            'title' => 'XSS Test',
            'content' => '<script>alert(1)</script>',
            'meta_description' => 'desc',
            'meta_keywords' => 'xss',
        ]);
        $response = $this->get('/pages/' . $page->slug);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
    }
}
