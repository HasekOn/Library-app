<?php

namespace App\Services;

use App\Exceptions\BookNotAvailableException;
use App\Exceptions\InvalidLoanStateException;
use App\Exceptions\MaxLoansExceededException;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;

class LoanService
{
    private const int MAX_ACTIVE_LOANS = 3;

    public function borrowBook(User $user, Book $book): Loan
    {
        if (!$book->isAvailable()) {
            throw new BookNotAvailableException();
        }

        $activeLoans = $user->loans()->active()->count();

        if ($activeLoans >= self::MAX_ACTIVE_LOANS) {
            throw new MaxLoansExceededException();
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

        $loan->update([
            'status' => Loan::STATUS_RETURNED,
            'returned_at' => now(),
        ]);

        $loan->book->increment('available_copies');

        return $loan;
    }
}
