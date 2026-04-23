<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->isStaff();
    }

    public function accept(User $user, Reservation $reservation): bool
    {
        return $user->isStaff()
            && $reservation->status === Reservation::STATUS_PENDING;
    }

    public function refuse(User $user, Reservation $reservation): bool
    {
        return $user->isStaff()
            && in_array($reservation->status, [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_ACCEPTED,
            ]);
    }

    public function markPaid(User $user, Reservation $reservation): bool
    {
        return $user->isStaff()
            && $reservation->status === Reservation::STATUS_WAITING_PAYMENT;
    }

    public function handleModification(User $user, Reservation $reservation): bool
    {
        return $user->isStaff()
            && $reservation->status === Reservation::STATUS_MODIFICATION_PENDING;
    }
}
