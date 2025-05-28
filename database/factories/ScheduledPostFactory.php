<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\ScheduledPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledPostFactory extends Factory
{
    protected $model = ScheduledPost::class;

    public function definition(): array
    {
        $post = Post::factory()->create();
        return [
            'post_id' => $post->id,
            'user_id' => $post->user_id,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'error_message' => null,
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }

    public function pending(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'error_message' => null,
            ];
        });
    }

    public function failed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'error_message' => $this->faker->sentence(),
            ];
        });
    }
}
