<?php

namespace App\Notifications;

use App\Models\Appointments;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentBookedNotification extends Notification
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
            ? 'A new appointment request was submitted.'
            : ($notifiable->user_type === 'Admin'
                ? 'A new appointment was booked.'
                : 'Your appointment request was submitted successfully.');

        return [
            'appointment_id' =>
                $this->appointment->id,

            'appointment_number' =>
                $this->appointment->appointment_number,

            'event' =>
                'AppointmentBooked',

            'title' =>
                'Appointment request submitted',

            'message' => $subject . ' Appointment '
                . $this->appointment->appointment_number . ' on '
                . $this->appointment->appointment_date->format('Y-m-d') . ' '
                . substr((string) $this->appointment->start_time, 0, 5) . '.',
            'appointment_date' => $this->appointment->appointment_date->format('Y-m-d'),
            'start_time' => (string) $this->appointment->start_time,
            'end_time' => (string) $this->appointment->end_time,
        ];
    }
}