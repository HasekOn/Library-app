<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use App\Services\LoanService;
use App\Exceptions\UnpaidFineException;
use App\Exceptions\BookReservedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanFineTest extends TestCase
{
    use RefreshDatabase;

    private LoanService $loanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loanService = app(LoanService::class);
    }

    public function test_late_return_calculates_fine(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($user, $book);

        // Posuneme čas o 17 dní (14 dní lhůta + 3 dny prodlení)
        $this->travel(17)->days();

        $returnedLoan = $this->loanService->returnBook($loan->fresh());

        $this->assertEquals(30, $returnedLoan->fine_amount); // 3 dny * 10 Kč
    }

    public function test_on_time_return_has_no_fine(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($user, $book);

        $this->travel(10)->days();

        $returnedLoan = $this->loanService->returnBook($loan->fresh());

        $this->assertEquals(0, $returnedLoan->fine_amount);
    }

    public function test_user_with_unpaid_fine_cannot_borrow(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($user, $book);

        $this->travel(17)->days();
        $this->loanService->returnBook($loan->fresh());

        $newBook = Book::factory()->create(['available_copies' => 1]);

        $this->expectException(UnpaidFineException::class);

        $this->loanService->borrowBook($user, $newBook);
    }

    public function test_cannot_borrow_book_reserved_by_another_user(): void
    {
        $reservingUser = User::factory()->create();
        $borrowingUser = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        Reservation::factory()->create([
            'user_id' => $reservingUser->id,
            'book_id' => $book->id,
            'status' => 'active',
            'expires_at' => now()->addDays(3),
        ]);

        $this->expectException(BookReservedException::class);

        $this->loanService->borrowBook($borrowingUser, $book);
    }

    public function test_user_can_borrow_book_they_reserved(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        Reservation::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'active',
            'expires_at' => now()->addDays(3),
        ]);

        $loan = $this->loanService->borrowBook($user, $book);

        $this->assertNotNull($loan);
        $this->assertEquals('borrowed', $loan->status);
    }
}
