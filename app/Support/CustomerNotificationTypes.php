<?php

namespace App\Support;

use App\Notifications\BookingStatusUpdatedNotification;
use App\Notifications\EventReservationStatusUpdatedNotification;
use App\Notifications\MembershipActivatedNotification;
use App\Notifications\PaymentStatusUpdatedNotification;

class CustomerNotificationTypes
{
    public const ALL = [
        BookingStatusUpdatedNotification::class,
        PaymentStatusUpdatedNotification::class,
        EventReservationStatusUpdatedNotification::class,
        MembershipActivatedNotification::class,
    ];
}
