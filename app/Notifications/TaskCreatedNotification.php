<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Task $task)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task created')
            ->line('Task created successfully.')
            ->line('Title: '.$this->task->title)
            ->line('Status: '.$this->task->status->value)
            ->line('Due date: '.($this->task->due_date?->format('Y-m-d') ?? '—'));
    }
}
