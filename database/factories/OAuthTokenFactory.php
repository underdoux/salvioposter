<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\OAuthToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class OAuthTokenFactory extends Factory
{
    protected $model = OAuthToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'access_token' => $this->faker->uuid(),
            'refresh_token' => $this->faker->uuid(),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 hour'),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }

    public function expired(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => $this->faker->dateTimeBetween('-1 day', '-1 hour'),
            ];
        });
    }

    public function valid(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+1 day'),
            ];
        });
    }
}
