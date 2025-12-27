<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Page;
use App\Models\ServiceCategory;

class SanitizationTest extends TestCase
{
    public function test_page_content_is_sanitized_when_set()
    {
        $page = new Page();

        $unsafe = '<p>Hello</p><script>alert("xss")</script><img src="x" onerror="alert(1)">';

        $page->content = $unsafe;

        $stored = $page->getAttributes()['content'];

        // Should not contain <script> or onerror attributes
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onerror', $stored);

        // Allowed tags like <p> should remain
        $this->assertStringContainsString('<p>Hello</p>', $stored);
    }

    public function test_service_category_icon_is_sanitized_and_allows_svg()
    {
        $cat = new ServiceCategory();

        $unsafe = '<svg><path d="M 0 0"/></svg><script>alert(1)</script><img src=x onerror=alert(1) />';

        $cat->icon = $unsafe;

        $stored = $cat->getAttributes()['icon'];

        // Should not contain script or img with onerror
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onerror', $stored);

        // Should contain sanitized svg path
        $this->assertStringContainsString('<svg', $stored);
        $this->assertStringContainsString('<path', $stored);
    }
}
