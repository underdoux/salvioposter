<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Post;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $types = ['analytics_update', 'post_published', 'post_failed', 'milestone_reached'];
        $type = $this->faker->randomElement($types);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'title' => $this->getTitleForType($type),
            'message' => $this->getMessageForType($type),
            'data' => $this->getDataForType($type),
            'read_at' => $this->faker->optional()->dateTime(),
            'read' => $this->faker->boolean(),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }

    protected function getTitleForType(string $type): string
    {
        return match($type) {
            'analytics_update' => 'Analytics Update',
            'post_published' => 'Post Published',
            'post_failed' => 'Post Failed',
            'milestone_reached' => 'Milestone Reached',
            default => 'Notification',
        };
    }

    protected function getMessageForType(string $type): string
    {
        $postTitle = $this->faker->sentence();
        
        return match($type) {
            'analytics_update' => "Your post '{$postTitle}' has new analytics data.",
            'post_published' => "Your post '{$postTitle}' has been published successfully.",
            'post_failed' => "Failed to publish your post '{$postTitle}'.",
            'milestone_reached' => "Your post '{$postTitle}' has reached a new milestone!",
            default => $this->faker->sentence(),
        };
    }

    protected function getDataForType(string $type): array
    {
        $post = Post::factory()->create();

        return match($type) {
            'analytics_update' => [
                'post_id' => $post->id,
                'metrics' => [
                    'views' => $this->faker->numberBetween(100, 1000),
                    'likes' => $this->faker->numberBetween(10, 100),
                    'comments' => $this->faker->numberBetween(5, 50),
                ],
            ],
            'post_published' => [
                'post_id' => $post->id,
            ],
            'post_failed' => [
                'post_id' => $post->id,
                'error' => $this->faker->sentence(),
            ],
            'milestone_reached' => [
                'post_id' => $post->id,
                'milestone' => $this->faker->randomElement(['1000 views', '100 likes', '50 comments']),
            ],
            default => [],
        };
    }

    public function unread(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'read_at' => null,
                'read' => false,
            ];
        });
    }

    public function read(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'read_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'read' => true,
            ];
        });
    }

    public function analyticsUpdate(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'analytics_update',
                'title' => $this->getTitleForType('analytics_update'),
                'message' => $this->getMessageForType('analytics_update'),
                'data' => $this->getDataForType('analytics_update'),
            ];
        });
    }

    public function postPublished(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'post_published',
                'title' => $this->getTitleForType('post_published'),
                'message' => $this->getMessageForType('post_published'),
                'data' => $this->getDataForType('post_published'),
            ];
        });
    }
}
