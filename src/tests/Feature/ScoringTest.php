<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Reglas de puntaje RF-27/RF-29 a través de la API de votos. */
class ScoringTest extends TestCase
{
    use RefreshDatabase;

    private function vote(User $user, Report $report, string $type)
    {
        Sanctum::actingAs($user);

        return $this->postJson("/api/reports/{$report->id}/votes", [
            'type' => $type,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
        ]);
    }

    private function scores($users): array
    {
        return collect($users)->map(fn (User $u) => $u->fresh()->score)->all();
    }

    public function test_verification_pays_owner_and_confirm_voters_and_coins(): void
    {
        $report = Report::factory()->create();
        $voters = User::factory()->count(5)->create();

        foreach ($voters as $voter) {
            $this->vote($voter, $report, 'confirm')->assertCreated();
        }

        $this->assertSame('verified', $report->fresh()->status);
        $owner = $report->user->fresh();
        $this->assertSame(10, $owner->score);
        $this->assertSame(10, $owner->coins, 'coins sube junto con score');
        $this->assertSame([2, 2, 2, 2, 2], $this->scores($voters));
    }

    public function test_confirm_after_verification_also_scores(): void
    {
        $report = Report::factory()->create();
        foreach (User::factory()->count(5)->create() as $voter) {
            $this->vote($voter, $report, 'confirm');
        }

        $late = User::factory()->create();
        $this->vote($late, $report, 'confirm')->assertCreated();

        $this->assertSame(2, $late->fresh()->score);
        $this->assertSame(10, $report->user->fresh()->score, 'el dueño no cobra de nuevo');
    }

    public function test_owner_scores_when_resolved_without_verification(): void
    {
        $report = Report::factory()->create();
        $resolvers = User::factory()->count(3)->create();

        foreach ($resolvers as $voter) {
            $this->vote($voter, $report, 'resolve');
        }

        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame([5, 5, 5], $this->scores($resolvers));
        $this->assertSame(10, $report->user->fresh()->score);
    }

    public function test_full_cycle_pays_each_award_once(): void
    {
        $report = Report::factory()->create();
        $confirmers = User::factory()->count(6)->create();
        foreach ($confirmers as $voter) {
            $this->vote($voter, $report, 'confirm');
        }
        // 6 confirm + 14 resolve = 70% resolve → resolved
        $resolvers = User::factory()->count(14)->create();
        foreach ($resolvers as $voter) {
            $this->vote($voter, $report, 'resolve');
        }

        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame(array_fill(0, 6, 2), $this->scores($confirmers));
        $this->assertSame(array_fill(0, 14, 5), $this->scores($resolvers));
        $this->assertSame(10, $report->user->fresh()->score);
    }

    public function test_retract_and_vote_again_does_not_pay_twice(): void
    {
        $report = Report::factory()->verified()->create(['votes_confirm' => 5]);
        $voter = User::factory()->create();

        $this->vote($voter, $report, 'confirm')->assertCreated();
        $this->assertSame(2, $voter->fresh()->score);

        Sanctum::actingAs($voter);
        $this->deleteJson("/api/reports/{$report->id}/votes/confirm")->assertOk();
        $this->vote($voter, $report, 'confirm')->assertCreated();

        $this->assertSame(2, $voter->fresh()->score);
    }

    public function test_level_up_uses_score(): void
    {
        $owner = User::factory()->create();
        foreach (range(1, 2) as $_) {
            $report = Report::factory()->for($owner)->create();
            foreach (User::factory()->count(5)->create() as $voter) {
                $this->vote($voter, $report, 'confirm');
            }
        }

        $owner->refresh();
        $this->assertSame(20, $owner->score);
        $this->assertSame(User::LEVEL_COLABORADOR, $owner->level);

        Sanctum::actingAs($owner);
        $this->getJson('/api/me')
            ->assertJsonPath('user.score', 20)
            ->assertJsonPath('user.coins', 20)
            ->assertJsonPath('user.level', 'colaborador')
            ->assertJsonPath('user.cosmetics.frame', null);
    }

    public function test_status_cannot_be_changed_manually(): void
    {
        $report = Report::factory()->create();

        Sanctum::actingAs(User::factory()->create());
        $this->patchJson("/api/reports/{$report->id}/status", ['status' => 'verified'])
            ->assertNotFound(); // la ruta ya no existe: el estado solo cambia por votos

        $this->assertSame('pending', $report->fresh()->status);
        $this->assertSame(0, $report->user->fresh()->score);
    }
}
