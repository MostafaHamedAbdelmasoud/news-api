<?php

namespace Tests\Feature\Api\V1;

use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_sources(): void
    {
        Source::factory()->count(5)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/sources');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug'],
                ],
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_only_active_sources_are_listed(): void
    {
        Source::factory()->count(3)->create(['is_active' => true]);
        Source::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/sources');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_show_single_source(): void
    {
        $source = Source::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/sources/{$source->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug'],
            ])
            ->assertJsonPath('data.id', $source->id)
            ->assertJsonPath('data.name', $source->name);
    }

    public function test_returns_404_for_nonexistent_source(): void
    {
        $response = $this->getJson('/api/v1/sources/99999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Source not found']);
    }

    public function test_returns_empty_array_when_no_sources(): void
    {
        $response = $this->getJson('/api/v1/sources');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
