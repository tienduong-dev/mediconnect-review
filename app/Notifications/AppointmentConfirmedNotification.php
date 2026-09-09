<?php

namespace App\Notifications;

use App\Models\Appointments;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the patient and the assigned doctor when an admin confirms a request.
 *
 * Stored through Laravel database notifications so the patient and doctor
 * modules can read them with the standard notifiable relation.
 */
class AppointmentConfirmedNotification extends Notification
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
            'event' => 'AppointmentConfirmed',
            'title' => 'Appointment confirmed',
            'message' => 'Appointment ' . $this->appointment->appointment_number
                . ' has been confirmed for '
                . $this->appointment->appointment_date->format('Y-m-d')
                . ' at ' . substr((string) $this->appointment->start_time, 0, 5) . '.',
        ];
    }
}
