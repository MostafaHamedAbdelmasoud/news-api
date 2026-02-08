<?php

namespace Tests\Feature\Api\V1;

use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_preferences(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/preferences');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'preferred_sources',
                    'preferred_categories',
                    'preferred_authors',
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_get_preferences(): void
    {
        $response = $this->getJson('/api/v1/user/preferences');

        $response->assertStatus(401);
    }

    public function test_user_can_update_preferred_sources(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create(['user_id' => $user->id]);
        $sources = Source::factory()->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/user/preferences', [
                'preferred_sources' => $sources->pluck('id')->toArray(),
            ]);

        $response->assertOk()
            ->assertJsonPath('data.preferred_sources', $sources->pluck('id')->toArray());

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_update_preferred_categories(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create(['user_id' => $user->id]);
        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/user/preferences', [
                'preferred_categories' => $categories->pluck('id')->toArray(),
            ]);

        $response->assertOk()
            ->assertJsonPath('data.preferred_categories', $categories->pluck('id')->toArray());
    }

    public function test_user_can_update_preferred_authors(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create(['user_id' => $user->id]);
        $authors = Author::factory()->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/user/preferences', [
                'preferred_authors' => $authors->pluck('id')->toArray(),
            ]);

        $response->assertOk()
            ->assertJsonPath('data.preferred_authors', $authors->pluck('id')->toArray());
    }

    public function test_user_can_update_all_preferences_at_once(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create(['user_id' => $user->id]);
        $sources = Source::factory()->count(2)->create();
        $categories = Category::factory()->count(2)->create();
        $authors = Author::factory()->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/user/preferences', [
                'preferred_sources' => $sources->pluck('id')->toArray(),
                'preferred_categories' => $categories->pluck('id')->toArray(),
                'preferred_authors' => $authors->pluck('id')->toArray(),
            ]);

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals($sources->pluck('id')->toArray(), $data['preferred_sources']);
        $this->assertEquals($categories->pluck('id')->toArray(), $data['preferred_categories']);
        $this->assertEquals($authors->pluck('id')->toArray(), $data['preferred_authors']);
    }

    public function test_user_can_clear_preferences(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'preferred_sources' => [1, 2, 3],
            'preferred_categories' => [1, 2],
            'preferred_authors' => [1],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/user/preferences', [
                'preferred_sources' => [],
                'preferred_categories' => [],
                'preferred_authors' => [],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.preferred_sources', [])
            ->assertJsonPath('data.preferred_categories', [])
            ->assertJsonPath('data.preferred_authors', []);
    }

    public function test_validates_nonexistent_source_ids(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/user/preferences', [
                'preferred_sources' => [99999],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferred_sources.0']);
    }

    public function test_validates_nonexistent_category_ids(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/user/preferences', [
                'preferred_categories' => [99999],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferred_categories.0']);
    }

    public function test_validates_nonexistent_author_ids(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/user/preferences', [
                'preferred_authors' => [99999],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferred_authors.0']);
    }

    public function test_unauthenticated_user_cannot_update_preferences(): void
    {
        $response = $this->putJson('/api/v1/user/preferences', [
            'preferred_sources' => [1],
        ]);

        $response->assertStatus(401);
    }

    public function test_existing_preferences_are_returned_on_get(): void
    {
        $user = User::factory()->create();
        $sources = Source::factory()->count(2)->create();

        UserPreference::factory()->create([
            'user_id' => $user->id,
            'preferred_sources' => $sources->pluck('id')->toArray(),
            'preferred_categories' => [],
            'preferred_authors' => [],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/preferences');

        $response->assertOk()
            ->assertJsonPath('data.preferred_sources', $sources->pluck('id')->toArray());
    }

    public function test_creates_preferences_on_first_update(): void
    {
        $user = User::factory()->create();
        $sources = Source::factory()->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/user/preferences', [
                'preferred_sources' => $sources->pluck('id')->toArray(),
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.preferred_sources', $sources->pluck('id')->toArray());
    }
}
