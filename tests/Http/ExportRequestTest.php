<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Telescope\Database\Factories\EntryModelFactory;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Tests\FeatureTestCase;
use Orchestra\Testbench\Http\Middleware\VerifyCsrfToken;

class ExportRequestTest extends FeatureTestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([Authorize::class, VerifyCsrfToken::class, ValidateCsrfToken::class]);
    }

    public function test_can_export_request_entry()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::REQUEST,
            'content' => [
                'method' => 'GET',
                'uri' => '/test-endpoint',
                'headers' => ['Accept' => 'application/json'],
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/requests/{$entry->uuid}/export");

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertHeader('Content-Disposition');

        $json = json_decode($response->getContent(), true);

        $this->assertEquals($entry->uuid, $json['id']);
        $this->assertEquals($entry->batch_id, $json['batch_id']);
        $this->assertEquals(EntryType::REQUEST, $json['type']);
        $this->assertArrayHasKey('content', $json);
        $this->assertArrayHasKey('family_hash', $json);
    }

    public function test_export_includes_proper_filename_in_header()
    {
        $entry = EntryModelFactory::new()->create(['type' => EntryType::REQUEST]);

        $response = $this->get("/telescope/telescope-api/requests/{$entry->uuid}/export");

        $contentDisposition = $response->headers->get('Content-Disposition');

        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString("telescope-request-{$entry->uuid}.json", $contentDisposition);
    }

    public function test_export_formats_json_with_proper_indentation()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::REQUEST,
            'content' => ['test' => 'data'],
        ]);

        $response = $this->get("/telescope/telescope-api/requests/{$entry->uuid}/export");

        $content = $response->getContent();

        $this->assertStringContainsString("\n", $content);
        $this->assertStringContainsString('    ', $content);

        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded);
    }

    public function test_export_returns_404_for_nonexistent_entry()
    {
        $response = $this->get('/telescope/telescope-api/requests/nonexistent-uuid-123/export');

        $response->assertNotFound();
    }

    public function test_export_includes_all_entry_fields()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::REQUEST,
            'content' => ['method' => 'POST'],
        ]);

        $response = $this->get("/telescope/telescope-api/requests/{$entry->uuid}/export");

        $json = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('id', $json);
        $this->assertArrayHasKey('batch_id', $json);
        $this->assertArrayHasKey('type', $json);
        $this->assertArrayHasKey('content', $json);
        $this->assertArrayHasKey('family_hash', $json);
        $this->assertArrayHasKey('sequence', $json);
        $this->assertArrayHasKey('created_at', $json);
        $this->assertArrayHasKey('tags', $json);
    }
}
