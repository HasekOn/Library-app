<?php

namespace App\Services;

use App\Exceptions\BookNotAvailableException;
use App\Exceptions\BookReservedException;
use App\Exceptions\InvalidLoanStateException;
use App\Exceptions\MaxLoansExceededException;
use App\Exceptions\UnpaidFineException;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\LateReturnNotification;

class LoanService
{
    private const int MAX_ACTIVE_LOANS = 3;
    private const int FINE_PER_DAY = 10;

    public function borrowBook(User $user, Book $book): Loan
    {
        if (!$book->isAvailable()) {
            throw new BookNotAvailableException();
        }

        if ($this->hasUnpaidFines($user)) {
            throw new UnpaidFineException();
        }

        $activeLoans = $user->loans()->active()->count();

        if ($activeLoans >= self::MAX_ACTIVE_LOANS) {
            throw new MaxLoansExceededException();
        }

        if ($this->isReservedByAnotherUser($book, $user)) {
            throw new BookReservedException();
        }

        $book->decrement('available_copies');

        return Loan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(14),
        ]);
    }

    public function returnBook(Loan $loan): Loan
    {
        if (!$loan->isBorrowed()) {
            throw new InvalidLoanStateException('Cannot return a loan that is not in borrowed state.');
        }

        $fineAmount = $this->calculateFine($loan);

        $loan->update([
            'status' => Loan::STATUS_RETURNED,
            'returned_at' => now(),
            'fine_amount' => $fineAmount,
        ]);

        $loan->book->increment('available_copies');

        if ($fineAmount > 0) {
            /** @var \App\Models\User $user */
            $user = $loan->user;
            $user->notify(new LateReturnNotification($fineAmount));
        }

        return $loan;
    }

    private function calculateFine(Loan $loan): int
    {
        /** @var \Carbon\Carbon $dueDate */
        $dueDate = $loan->due_at;
        $now = now();

        if ($now->lte($dueDate)) {
            return 0;
        }

        $daysLate = (int) $dueDate->diffInDays($now);

        return $daysLate * self::FINE_PER_DAY;
    }

    private function hasUnpaidFines(User $user): bool
    {
        return $user->loans()->withUnpaidFines()->exists();
    }

    private function isReservedByAnotherUser(Book $book, User $user): bool
    {
        return Reservation::where('book_id', $book->id)
            ->where('user_id', '!=', $user->id)
            ->active()
            ->exists();
    }
}
