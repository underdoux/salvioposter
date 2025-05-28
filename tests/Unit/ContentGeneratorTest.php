<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ContentGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContentGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected $contentGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentGenerator = app(ContentGeneratorService::class);
    }

    public function test_can_generate_title()
    {
        $keywords = ['technology', 'AI', 'future'];
        $title = $this->contentGenerator->generateTitle($keywords);
        
        $this->assertNotEmpty($title);
        $this->assertIsString($title);
    }

    public function test_can_generate_content()
    {
        $title = 'The Future of AI Technology';
        $content = $this->contentGenerator->generateContent($title);
        
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
        $this->assertStringContainsString($title, $content);
    }

    public function test_generates_valid_html_content()
    {
        $title = 'Test Post';
        $content = $this->contentGenerator->generateContent($title);
        
        $this->assertStringContainsString('<p>', $content);
        $this->assertStringContainsString('</p>', $content);
    }

    public function test_handles_empty_input()
    {
        $content = $this->contentGenerator->generateContent('');
        
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
    }

    public function test_content_meets_minimum_length()
    {
        $title = 'Short Test';
        $content = $this->contentGenerator->generateContent($title);
        
        $this->assertGreaterThan(100, strlen(strip_tags($content)));
    }

    public function test_content_includes_required_sections()
    {
        $title = 'Complete Blog Post';
        $content = $this->contentGenerator->generateContent($title);
        
        $this->assertStringContainsString('<h1>', $content);
        $this->assertStringContainsString('<p>', $content);
        $this->assertStringContainsString('</h1>', $content);
        $this->assertStringContainsString('</p>', $content);
    }
}
