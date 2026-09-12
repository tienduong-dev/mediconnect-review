<?php

namespace App\Notifications;

use App\Models\Appointments;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentRescheduledNotification extends Notification
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
            ? 'A patient rescheduled an appointment.'
            : ($notifiable->user_type === 'Admin'
                ? 'An appointment was rescheduled.'
                : 'Your appointment was rescheduled successfully.');

        return [
            'appointment_id' =>
                $this->appointment->id,

            'appointment_number' =>
                $this->appointment->appointment_number,

            'event' =>
                'AppointmentRescheduled',

            'title' =>
                'Appointment rescheduled',

            'message' => $subject . ' Appointment '
                . $this->appointment->appointment_number . ' is now on '
                . $this->appointment->appointment_date->format('Y-m-d') . ' '
                . substr((string) $this->appointment->start_time, 0, 5) . '.',
            'appointment_date' => $this->appointment->appointment_date->format('Y-m-d'),
            'start_time' => (string) $this->appointment->start_time,
            'end_time' => (string) $this->appointment->end_time,
            'reason' => $this->appointment->reschedule_reason,
        ];
    }
}