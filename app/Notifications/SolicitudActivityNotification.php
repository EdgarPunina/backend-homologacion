<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SolicitudActivityNotification extends Notification
{
    public function __construct(public int $solicitudId, public string $event, public string $message) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'solicitud_id' => $this->solicitudId,
            'evento' => $this->event,
            'mensaje' => $this->message,
            'url' => route('student.solicitudes.show', $this->solicitudId, false),
        ];
    }
}
