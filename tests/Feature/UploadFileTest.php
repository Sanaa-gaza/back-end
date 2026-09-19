<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_upload_a_valid_image(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/uploads', [
            'file' => UploadedFile::fake()->image('photo.jpg', 100, 100),
        ]);

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['path', 'url']]);

        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    public function test_upload_fails_without_a_file(): void
    {
        $response = $this->postJson('/api/uploads', []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('file');
    }

    public function test_upload_rejects_a_file_exceeding_the_size_limit(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/uploads', [
            'file' => UploadedFile::fake()->image('big.jpg')->size(10241),
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('file');
    }

    public function test_upload_rejects_a_disallowed_file_type(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/uploads', [
            'file' => UploadedFile::fake()->create('resume.txt', 10, 'text/plain'),
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('file');
    }
}
