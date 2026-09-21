<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recording>
 */
class RecordingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resi' => 'SPX' . $this->faker->numberBetween(100000, 999999),
            'platform' => $this->faker->randomElement(['web', 'mobile', 'api']),
            'status' => $this->faker->randomElement(['queued', 'processing', 'completed', 'failed', 'cancelled']),
            'source_path' => null,
            'final_path' => null,
            'duration' => null,
            'file_size' => null,
            'mime_type' => 'video/mp4',
            'recorded_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'processed_at' => null,
            'error_message' => null,
        ];
    }
}
