<?php

namespace Tests\Feature;

use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** GET /api/reports/stream/changes: el sondeo de la app cada 30 s. */
class ReportStreamTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function changes(string $since)
    {
        return $this->getJson('/api/reports/stream/changes?since=' . urlencode($since));
    }

    public function test_since_in_utc_finds_new_reports(): void
    {
        Carbon::setTestNow('2026-11-13 14:00:00');
        $since = now()->subSeconds(10);
        Report::factory()->create();

        // Así manda la app el primer sondeo: UTC con "Z".
        $this->changes($since->copy()->utc()->format('Y-m-d\TH:i:s\Z'))->assertOk()->assertJsonPath('count', 1);
        // Y así los siguientes: el timestamp del servidor, con offset -06:00.
        $this->changes($since->copy()->format(DATE_ATOM))->assertOk()->assertJsonPath('count', 1);
    }

    public function test_since_excludes_older_reports_in_any_timezone(): void
    {
        Carbon::setTestNow('2026-11-13 13:00:00');
        Report::factory()->create();

        Carbon::setTestNow('2026-11-13 14:00:00');
        $since = now()->subMinute();

        $this->changes($since->copy()->utc()->format('Y-m-d\TH:i:s\Z'))->assertJsonPath('count', 0);
        $this->changes($since->copy()->format(DATE_ATOM))->assertJsonPath('count', 0);
    }

    public function test_timestamp_returned_is_usable_as_next_since(): void
    {
        Carbon::setTestNow('2026-11-13 14:00:00');
        $ts = $this->getJson('/api/reports/stream/changes')->assertOk()->json('timestamp');

        Carbon::setTestNow('2026-11-13 14:00:20');
        Report::factory()->create();

        $this->getJson('/api/reports/stream/changes?since=' . urlencode($ts))->assertJsonPath('count', 1);
    }
}
