<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_categories(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug'],
                ],
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_can_show_single_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug'],
            ])
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', $category->name);
    }

    public function test_returns_404_for_nonexistent_category(): void
    {
        $response = $this->getJson('/api/v1/categories/99999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Category not found']);
    }

    public function test_returns_empty_array_when_no_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_categories_are_ordered_by_name(): void
    {
        Category::factory()->create(['name' => 'Zebra']);
        Category::factory()->create(['name' => 'Alpha']);
        Category::factory()->create(['name' => 'Beta']);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $data = $response->json('data');

        $names = array_column($data, 'name');
        $sortedNames = $names;
        sort($sortedNames);

        $this->assertEquals($sortedNames, $names);
    }
}
