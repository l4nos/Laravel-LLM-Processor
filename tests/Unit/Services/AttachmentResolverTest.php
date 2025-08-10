<?php

namespace Lanos\LLMProcessor\Tests\Unit\Services;

use Lanos\LLMProcessor\Services\AttachmentResolver;
use Lanos\LLMProcessor\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class AttachmentResolverTest extends TestCase
{
    /**
     * @var AttachmentResolver
     */
    protected $attachmentResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attachmentResolver = new AttachmentResolver();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(AttachmentResolver::class, $this->attachmentResolver);
    }

    /** @test */
    public function it_can_validate_url()
    {
        // Mock a successful HTTP response
        Http::fake([
            'https://example.com/*' => Http::response('', 200),
        ]);

        $result = $this->attachmentResolver->validateUrl('https://example.com/test.jpg');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_for_invalid_url()
    {
        $result = $this->attachmentResolver->validateUrl('not-a-url');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_resolve_attachment_paths()
    {
        // Mock HTTP responses
        Http::fake([
            'https://example.com/image1.jpg' => Http::response('image1-content', 200, ['Content-Type' => 'image/jpeg']),
            'https://example.com/image2.jpg' => Http::response('image2-content', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $attachmentPaths = [
            'images.0.url',
            'images.1.url'
        ];

        $modelData = [
            'images' => [
                ['url' => 'https://example.com/image1.jpg'],
                ['url' => 'https://example.com/image2.jpg']
            ]
        ];

        $attachments = $this->attachmentResolver->resolve($attachmentPaths, $modelData);

        $this->assertCount(2, $attachments);
        $this->assertArrayHasKey('url', $attachments[0]);
        $this->assertArrayHasKey('original_url', $attachments[0]);
        $this->assertArrayHasKey('mime_type', $attachments[0]);
    }

    /** @test */
    public function it_handles_failed_attachment_downloads_gracefully()
    {
        // Mock HTTP responses with one failure
        Http::fake([
            'https://example.com/image1.jpg' => Http::response('image1-content', 200, ['Content-Type' => 'image/jpeg']),
            'https://example.com/image2.jpg' => Http::response('Not Found', 404),
        ]);

        $attachmentPaths = [
            'images.0.url',
            'images.1.url'
        ];

        $modelData = [
            'images' => [
                ['url' => 'https://example.com/image1.jpg'],
                ['url' => 'https://example.com/image2.jpg']
            ]
        ];

        $attachments = $this->attachmentResolver->resolve($attachmentPaths, $modelData);

        // Should still return the successfully downloaded attachment
        $this->assertCount(1, $attachments);
    }
}