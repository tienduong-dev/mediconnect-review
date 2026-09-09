<?php

namespace App\Notifications;

use App\Models\Appointments;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the patient when an admin rejects a pending request.
 *
 * Stored through Laravel database notifications so the patient and doctor
 * modules can read them with the standard notifiable relation.
 */
class AppointmentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Appointments $appointment,
        protected ?string $reason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'appointment_number' => $this->appointment->appointment_number,
            'event' => 'AppointmentRejected',
            'title' => 'Appointment rejected',
            'message' => 'Appointment ' . $this->appointment->appointment_number
                . ' was rejected'
                . ($this->reason ? ': ' . $this->reason : '.'),
        ];
    }
}
