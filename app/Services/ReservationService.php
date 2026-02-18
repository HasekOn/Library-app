<?php


namespace App\Services;

use App\Exceptions\BookAvailableForLoanException;
use App\Exceptions\InvalidReservationStateException;
use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;

class ReservationService
{
    public function reserveBook(User $user, Book $book): Reservation
    {
        if ($book->isAvailable()) {
            throw new BookAvailableForLoanException();
        }

        return Reservation::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => Reservation::STATUS_ACTIVE,
            'expires_at' => now()->addDays(3),
        ]);
    }

    public function cancelReservation(Reservation $reservation): Reservation
    {
        if (!$reservation->isActive()) {
            throw new InvalidReservationStateException('Cannot cancel a reservation that is not active.');
        }

        $reservation->update([
            'status' => Reservation::STATUS_CANCELLED,
        ]);

        return $reservation;
    }
}
