<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_creation_requires_authentication(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Новая задача',
            'description' => 'Детали задачи',
            'status' => 'pending',
            'due_date' => '2024-12-31',
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_can_create_task_with_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $payload = [
            'title' => 'Купить молоко',
            'description' => 'В магазине у дома',
            'status' => 'pending',
            'due_date' => '2024-12-31',
        ];

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks', $payload);

        $response
            ->assertCreated()
            ->assertHeader('Location')
            ->assertJsonFragment([
                'title' => $payload['title'],
                'description' => $payload['description'],
                'status' => $payload['status'],
                'due_date' => $payload['due_date'],
            ]);

        $taskId = $response->json('id');

        $this->assertNotNull($taskId);

        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'user_id' => $user->id,
            'title' => $payload['title'],
        ]);

        Notification::assertSentTo(
            [$user],
            TaskCreatedNotification::class
        );
    }

    public function test_user_cannot_access_other_users_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $task = Task::factory()->for($owner)->create();
        $otherToken = $otherUser->createToken('api')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$otherToken)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertForbidden();
    }
}
