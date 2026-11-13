<?php

namespace Tests\Feature;

use App\Events\ReportCreated;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminReportPinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.admin.user' => 'admin', 'services.admin.password' => 'secreto']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Basic ' . base64_encode('admin:secreto')];
    }

    public function test_form_requires_credentials_and_lists_options(): void
    {
        $user = User::factory()->create(['name' => 'Ana Pérez']);
        Category::factory()->create(['name' => 'Bache', 'active' => true]);

        $this->get('/admin/reports/create')->assertUnauthorized();
        $this->get('/admin/reports/create', $this->auth())->assertOk()->assertSee('Ana Pérez')->assertSee('Bache');
    }

    public function test_creates_pending_report_anywhere_for_chosen_user(): void
    {
        Event::fake([ReportCreated::class]);
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->post('/admin/reports', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'latitude' => 40.4168,
            'longitude' => -3.7038,
            'description' => 'Pin de prueba',
        ], $this->auth())->assertRedirect('/admin/reports/create')->assertSessionHas('status');

        $report = Report::sole();
        $this->assertSame($user->id, $report->user_id);
        $this->assertSame('pending', $report->status);
        $this->assertEqualsWithDelta(40.4168, (float) $report->latitude, 1e-6);
        Event::assertDispatched(ReportCreated::class);
    }

    public function test_validates_input_and_requires_auth(): void
    {
        $category = Category::factory()->create();

        $this->post('/admin/reports', [])->assertUnauthorized();
        $this->post('/admin/reports', [
            'user_id' => 999,
            'category_id' => $category->id,
            'latitude' => 120,
            'description' => 'x',
        ], $this->auth())->assertSessionHasErrors(['user_id', 'latitude', 'longitude']);

        $this->assertSame(0, Report::count());
    }
}
