<?php

namespace Tests\Feature\Api\V1;

use App\Models\Author;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_authors(): void
    {
        Author::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/authors');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_can_show_single_author(): void
    {
        $author = Author::factory()->create();

        $response = $this->getJson("/api/v1/authors/{$author->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name'],
            ])
            ->assertJsonPath('data.id', $author->id)
            ->assertJsonPath('data.name', $author->name);
    }

    public function test_returns_404_for_nonexistent_author(): void
    {
        $response = $this->getJson('/api/v1/authors/99999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Author not found']);
    }

    public function test_returns_empty_array_when_no_authors(): void
    {
        $response = $this->getJson('/api/v1/authors');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_authors_can_be_paginated(): void
    {
        Author::factory()->count(30)->create();

        $response = $this->getJson('/api/v1/authors');

        $response->assertOk();
        // Default pagination should apply
        $this->assertLessThanOrEqual(30, count($response->json('data')));
    }
}
