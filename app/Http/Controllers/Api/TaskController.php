<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Notifications\TaskCreatedNotification;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(Request $request)
    {
        $tasks = $request->user()
            ->tasks()
            ->latest()
            ->paginate();

        return TaskResource::collection($tasks);
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource($task);
    }

    public function store(StoreTaskRequest $request)
    {
        $task = $request->user()
            ->tasks()
            ->create($request->validated() + [
                'status' => $request->validated('status', TaskStatus::Pending->value),
            ]);

        $request->user()->notify(new TaskCreatedNotification($task));

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('tasks.show', $task));
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->fill($request->validated());

        if ($task->isDirty('due_date')) {
            $dueDate = $task->due_date;

            if ($dueDate === null || $dueDate->isToday() || $dueDate->isFuture()) {
                $task->overdue_notified_at = null;
            }
        }

        if ($task->isDirty('status') && $task->status === TaskStatus::Completed) {
            $task->overdue_notified_at = null;
        }

        $task->save();

        return new TaskResource($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }
}
