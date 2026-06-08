<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Centro aproximado de San Miguel, El Salvador: 13.4833, -88.1833.
     * Genera puntos dentro de ~3km alrededor del centro.
     */
    public function definition(): array
    {
        $centerLat = 13.4833;
        $centerLng = -88.1833;
        $offset = fn () => fake()->randomFloat(6, -0.027, 0.027);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'latitude' => $centerLat + $offset(),
            'longitude' => $centerLng + $offset(),
            'description' => fake()->sentence(12),
            'photo_path' => null,
            'status' => 'pending',
            'votes_confirm' => 0,
            'votes_resolve' => 0,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => 'verified',
            'status_changed_at' => now(),
            'verified_at' => now(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => 'resolved',
            'status_changed_at' => now(),
            'verified_at' => now()->subDay(),
            'resolved_at' => now(),
        ]);
    }
}
