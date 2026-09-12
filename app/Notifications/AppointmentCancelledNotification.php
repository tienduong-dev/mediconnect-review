<?php

namespace App\Notifications;

use App\Models\Appointments;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Appointments $appointment
    ) {
    }


    public function via(object $notifiable): array
    {
        return ['database'];
    }


    public function toDatabase(object $notifiable): array
    {
        $subject = $notifiable->user_type === 'Doctor'
            ? 'A patient cancelled an appointment.'
            : ($notifiable->user_type === 'Admin'
                ? 'An appointment was cancelled.'
                : 'Your appointment has been cancelled.');

        return [
            'appointment_id' =>
                $this->appointment->id,

            'appointment_number' =>
                $this->appointment->appointment_number,

            'event' =>
                'AppointmentCancelled',

            'title' =>
                'Appointment cancelled',

            'message' => $subject . ' Appointment '
                . $this->appointment->appointment_number . ' on '
                . $this->appointment->appointment_date->format('Y-m-d') . '.',
            'appointment_date' => $this->appointment->appointment_date->format('Y-m-d'),
            'start_time' => (string) $this->appointment->start_time,
            'end_time' => (string) $this->appointment->end_time,
            'reason' => $this->appointment->cancellation_reason,
        ];
    }
}