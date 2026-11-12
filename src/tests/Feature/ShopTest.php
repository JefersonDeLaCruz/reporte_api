<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Tienda de cosméticos: compra con coins, nivel mínimo, equipar y visibilidad. */
class ShopTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(int $coins, int $score = 0): User
    {
        $user = User::factory()->create();
        $user->forceFill([
            'coins' => $coins,
            'score' => $score,
            'level' => User::levelForScore($score),
        ])->save();

        return $user;
    }

    public function test_catalog_lists_items_with_state(): void
    {
        Sanctum::actingAs($this->userWith(0));

        $response = $this->getJson('/api/shop')->assertOk()->assertJsonPath('coins', 0);
        $items = collect($response->json('items'))->keyBy('slug');

        $this->assertTrue($items->has('marco-hoja'));
        $this->assertFalse($items['marco-hoja']['owned']);
        $this->assertFalse($items['marco-hoja']['locked']);
        $this->assertTrue($items['marco-dorado']['locked'], 'requiere nivel guardian');
        $this->assertSame(['#2E9E5B'], $items['marco-hoja']['value']['colors']);
    }

    public function test_purchase_spends_coins_but_keeps_score_and_level(): void
    {
        $user = $this->userWith(coins: 40, score: 40);
        Sanctum::actingAs($user);

        $this->postJson('/api/shop/marco-hoja/purchase')->assertCreated()->assertJsonPath('coins', 10);

        $user->refresh();
        $this->assertSame(10, $user->coins);
        $this->assertSame(40, $user->score);
        $this->assertSame(User::LEVEL_COLABORADOR, $user->level);
    }

    public function test_cannot_buy_twice_without_funds_or_below_level(): void
    {
        $user = $this->userWith(coins: 35);
        Sanctum::actingAs($user);

        $this->postJson('/api/shop/marco-hoja/purchase')->assertCreated();
        $this->postJson('/api/shop/marco-hoja/purchase')->assertStatus(409);
        $this->postJson('/api/shop/marco-oceano/purchase')->assertStatus(422);

        $rich = $this->userWith(coins: 1000);
        Sanctum::actingAs($rich);
        $this->postJson('/api/shop/marco-dorado/purchase')->assertForbidden();
        $this->assertSame(1000, $rich->fresh()->coins);
    }

    public function test_equip_requires_ownership_and_type(): void
    {
        $user = $this->userWith(coins: 100);
        Sanctum::actingAs($user);

        $this->putJson('/api/me/cosmetics', ['frame' => 'marco-hoja'])->assertStatus(422);

        $this->postJson('/api/shop/marco-hoja/purchase')->assertCreated();
        $this->putJson('/api/me/cosmetics', ['title' => 'marco-hoja'])->assertStatus(422);

        $this->putJson('/api/me/cosmetics', ['frame' => 'marco-hoja'])
            ->assertOk()
            ->assertJsonPath('user.cosmetics.frame.slug', 'marco-hoja')
            ->assertJsonPath('user.cosmetics.frame.colors.0', '#2E9E5B');

        $this->putJson('/api/me/cosmetics', ['frame' => null])
            ->assertOk()
            ->assertJsonPath('user.cosmetics.frame', null);
    }

    public function test_equipped_frame_and_title_are_public_but_theme_is_not(): void
    {
        $user = $this->userWith(coins: 200);
        Sanctum::actingAs($user);
        foreach (['marco-hoja', 'titulo-vecino', 'tema-menta'] as $slug) {
            $this->postJson("/api/shop/{$slug}/purchase")->assertCreated();
        }
        $this->putJson('/api/me/cosmetics', [
            'frame' => 'marco-hoja', 'title' => 'titulo-vecino', 'theme' => 'tema-menta',
        ])->assertOk();

        $report = Report::factory()->for($user)->create();

        $this->getJson("/api/reports/{$report->id}")
            ->assertJsonPath('report.user.cosmetics.frame.slug', 'marco-hoja')
            ->assertJsonPath('report.user.cosmetics.title.text', 'Vecino atento')
            ->assertJsonPath('report.user.cosmetics.theme', null);

        $this->getJson('/api/me')->assertJsonPath('user.cosmetics.theme.color', '#CFEBDD');
    }
}
