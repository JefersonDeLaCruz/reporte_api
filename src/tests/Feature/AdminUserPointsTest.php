<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserPointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.admin.user' => 'admin', 'services.admin.password' => 'secreto']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function auth(string $password = 'secreto'): array
    {
        return ['Authorization' => 'Basic ' . base64_encode("admin:{$password}")];
    }

    public function test_panel_is_hidden_without_configured_credentials(): void
    {
        config(['services.admin.user' => null, 'services.admin.password' => null]);

        $this->get('/admin/users')->assertNotFound();
    }

    public function test_panel_requires_valid_credentials(): void
    {
        $this->get('/admin/users')->assertUnauthorized()->assertHeader('WWW-Authenticate');
        $this->get('/admin/users', $this->auth('mala'))->assertUnauthorized();
    }

    public function test_lists_and_searches_users(): void
    {
        User::factory()->create(['name' => 'Ana Pérez', 'email' => 'ana@example.com']);
        User::factory()->create(['name' => 'Beto Gómez', 'email' => 'beto@example.com']);

        $this->get('/admin/users', $this->auth())->assertOk()->assertSee('Ana Pérez')->assertSee('Beto Gómez');
        $this->get('/admin/users?q=ana', $this->auth())->assertOk()->assertSee('Ana Pérez')->assertDontSee('Beto Gómez');
    }

    public function test_add_points_raises_score_coins_and_level(): void
    {
        $user = User::factory()->create(['score' => 15, 'level' => User::LEVEL_NUEVO]);

        $this->post("/admin/users/{$user->id}/points", ['points' => 10], $this->auth())
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame(25, $user->score);
        $this->assertSame(10, $user->coins);
        $this->assertSame(User::LEVEL_COLABORADOR, $user->level);
    }

    public function test_add_points_validates_and_requires_auth(): void
    {
        $user = User::factory()->create(['score' => 0]);

        $this->post("/admin/users/{$user->id}/points", ['points' => 10])->assertUnauthorized();
        $this->post("/admin/users/{$user->id}/points", ['points' => 0], $this->auth())->assertSessionHasErrors('points');
        $this->post("/admin/users/{$user->id}/points", ['points' => -5], $this->auth())->assertSessionHasErrors('points');

        $this->assertSame(0, $user->fresh()->score);
    }
}
