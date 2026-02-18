<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    private LoanService $loanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loanService = app(LoanService::class);
    }

    public function test_user_can_borrow_available_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 2]);

        $loan = $this->loanService->borrowBook($user, $book);

        $this->assertNotNull($loan);
        $this->assertEquals('borrowed', $loan->status);
        $this->assertEquals(1, $book->fresh()->available_copies);
    }

    public function test_cannot_borrow_book_with_no_available_copies(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $this->expectException(\App\Exceptions\BookNotAvailableException::class);

        $this->loanService->borrowBook($user, $book);
    }

    public function test_user_cannot_have_more_than_three_active_loans(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $book = Book::factory()->create(['available_copies' => 1]);
            $this->loanService->borrowBook($user, $book);
        }

        $extraBook = Book::factory()->create(['available_copies' => 1]);

        $this->expectException(\App\Exceptions\MaxLoansExceededException::class);

        $this->loanService->borrowBook($user, $extraBook);
    }

    public function test_user_can_return_borrowed_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 2]);

        $loan = $this->loanService->borrowBook($user, $book);
        $returnedLoan = $this->loanService->returnBook($loan);

        $this->assertEquals('returned', $returnedLoan->status);
        $this->assertNotNull($returnedLoan->returned_at);
        $this->assertEquals(2, $book->fresh()->available_copies);
    }

    public function test_cannot_return_already_returned_loan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($user, $book);
        $this->loanService->returnBook($loan);

        $this->expectException(\App\Exceptions\InvalidLoanStateException::class);

        $this->loanService->returnBook($loan->fresh());
    }
}
