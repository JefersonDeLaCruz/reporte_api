<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Rutas eliminadas porque la app no las usa y no hay roles: cualquier usuario con
 * sesión podía alterar categorías o listar los correos de todos los usuarios.
 */
class RemovedRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_writes_and_user_list_are_gone(): void
    {
        $category = Category::factory()->create(['name' => 'Original']);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/users')->assertNotFound();
        $this->postJson('/api/categories', ['name' => 'X', 'slug' => 'x', 'icon' => 'x'])->assertStatus(405);
        $this->putJson("/api/categories/{$category->id}", ['name' => 'Cambiada'])->assertStatus(405);
        $this->deleteJson("/api/categories/{$category->id}")->assertStatus(405);

        $this->assertSame('Original', $category->fresh()->name);
    }

    public function test_category_reads_still_work(): void
    {
        $category = Category::factory()->create();

        $this->getJson('/api/categories')->assertOk();
        $this->getJson("/api/categories/{$category->id}")->assertOk()->assertJsonPath('category.id', $category->id);
    }
}
