<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'status' => $this->faker->randomElement(['draft', 'posted']),
            'blogger_post_id' => $this->faker->uuid(),
            'published_at' => $this->faker->optional()->dateTime(),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }

    public function draft(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
                'published_at' => null,
                'blogger_post_id' => null,
            ];
        });
    }

    public function published(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'posted',
                'published_at' => $this->faker->dateTime(),
                'blogger_post_id' => $this->faker->uuid(),
            ];
        });
    }
}
