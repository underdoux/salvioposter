<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostAnalytics;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostAnalyticsFactory extends Factory
{
    protected $model = PostAnalytics::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'views' => $this->faker->numberBetween(0, 10000),
            'likes' => $this->faker->numberBetween(0, 1000),
            'comments' => $this->faker->numberBetween(0, 500),
            'daily_views' => [
                $this->faker->date() => $this->faker->numberBetween(0, 100),
                $this->faker->date() => $this->faker->numberBetween(0, 100),
                $this->faker->date() => $this->faker->numberBetween(0, 100),
            ],
            'referrers' => [
                'google.com' => $this->faker->numberBetween(0, 500),
                'facebook.com' => $this->faker->numberBetween(0, 300),
                'twitter.com' => $this->faker->numberBetween(0, 200),
            ],
            'engagement_score' => $this->faker->randomFloat(2, 0, 10),
            'last_synced_at' => $this->faker->dateTime(),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }

    public function highEngagement(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'views' => $this->faker->numberBetween(5000, 10000),
                'likes' => $this->faker->numberBetween(500, 1000),
                'comments' => $this->faker->numberBetween(200, 500),
                'engagement_score' => $this->faker->randomFloat(2, 7, 10),
            ];
        });
    }

    public function lowEngagement(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'views' => $this->faker->numberBetween(0, 100),
                'likes' => $this->faker->numberBetween(0, 10),
                'comments' => $this->faker->numberBetween(0, 5),
                'engagement_score' => $this->faker->randomFloat(2, 0, 3),
            ];
        });
    }
}
