<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Schedule;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifier;
use Illuminate\Support\Str;

/**
 * Who hears about an appointment: the counsellor, and the attendee at their own email address.
 *
 * A service user is only emailed when they have agreed to communications and have a real address (not the
 * placeholder generated for people with no email), because an appointment email could reveal that someone
 * is in touch with a recovery service.
 */
final class AppointmentRecipients
{
    public static function notify(Schedule $appointment, Notification $notification): void
    {
        $appointment->counsellor()?->notify($notification);

        $attendee = self::attendeeAddress($appointment);

        if ($attendee !== null) {
            Notifier::route('mail', [$attendee['email'] => $attendee['name']])->notify($notification);
        }
    }

    /**
     * @return array{email: string, name: string}|null
     */
    public static function attendeeAddress(Schedule $appointment): ?array
    {
        if ($appointment->isServiceUserAppointment()) {
            $person = $appointment->serviceUserRecord();

            if ($person === null || $person->consent_communications !== true) {
                return null;
            }

            $email = (string) $person->email;
        } else {
            $email = (string) $appointment->getExternalAttendeeEmail();
        }

        $placeholderDomain = '@'.Str::lower((string) config('app.temp_email_domain', 'spinney.local'));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || Str::endsWith(Str::lower($email), $placeholderDomain)) {
            return null;
        }

        return ['email' => $email, 'name' => $appointment->attendeeName()];
    }
}
