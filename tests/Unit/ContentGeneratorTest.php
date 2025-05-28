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
        $topic = 'artificial intelligence';
        $result = $this->contentGenerator->generateTitle($topic);
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['titles']);
        $this->assertIsArray($result['titles']);
        $this->assertStringContainsString('artificial intelligence', strtolower($result['titles'][0]));
    }

    public function test_can_generate_content()
    {
        $title = 'The Future of AI Technology';
        $result = $this->contentGenerator->generateContent($title);
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['content']);
        $this->assertIsString($result['content']);
        $this->assertStringContainsString('ai technology', strtolower($result['content']));
    }

    public function test_generates_markdown_content()
    {
        $title = 'Test Post';
        $result = $this->contentGenerator->generateContent($title);
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('# Introduction', $result['content']);
        $this->assertStringContainsString('## ', $result['content']);
    }

    public function test_handles_empty_input()
    {
        $result = $this->contentGenerator->generateContent('');
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['content']);
        $this->assertIsString($result['content']);
    }

    public function test_content_meets_minimum_length()
    {
        $title = 'Short Test';
        $result = $this->contentGenerator->generateContent($title);
        
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(100, strlen($result['content']));
    }

    public function test_content_includes_required_sections()
    {
        $title = 'Complete Blog Post';
        $result = $this->contentGenerator->generateContent($title);
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('# Introduction', $result['content']);
        $this->assertStringContainsString('## ', $result['content']);
        $this->assertStringContainsString('# Conclusion', $result['content']);
    }
}
