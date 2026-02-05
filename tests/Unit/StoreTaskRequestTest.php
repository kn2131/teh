<?php

namespace Tests\Unit;

use App\Http\Requests\Tasks\StoreTaskRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTaskRequestTest extends TestCase
{
    public function test_store_task_request_rejects_invalid_payload(): void
    {
        $rules = (new StoreTaskRequest())->rules();

        $validator = Validator::make([
            'title' => str_repeat('a', 256),
            'status' => 'wrong',
            'due_date' => '31-12-2024',
        ], $rules);

        $this->assertTrue($validator->fails());

        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('status', $errors);
        $this->assertArrayHasKey('due_date', $errors);
    }

    public function test_store_task_request_accepts_valid_payload(): void
    {
        $rules = (new StoreTaskRequest())->rules();

        $validator = Validator::make([
            'title' => 'Новая задача',
            'description' => 'Детали задачи',
            'status' => 'pending',
            'due_date' => '2024-12-31',
        ], $rules);

        $this->assertTrue($validator->passes());
    }
}
