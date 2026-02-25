<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private LoanService $loanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loanService = app(LoanService::class);
    }

    public function test_librarian_can_mark_fine_as_paid(): void
    {
        $this->freezeTime();

        $librarian = User::factory()->create(['role' => 'librarian']);
        $reader = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($reader, $book);
        $this->travel(17)->days();
        $this->loanService->returnBook($loan->fresh());

        $response = $this->actingAs($librarian)
            ->patchJson("/api/loans/{$loan->id}/pay-fine");

        $response->assertStatus(200);
        $this->assertTrue($loan->fresh()->fine_paid);
    }

    public function test_reader_cannot_mark_fine_as_paid(): void
    {
        $this->freezeTime();

        $reader = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($reader, $book);
        $this->travel(17)->days();
        $this->loanService->returnBook($loan->fresh());

        $response = $this->actingAs($reader)
            ->patchJson("/api/loans/{$loan->id}/pay-fine");

        $response->assertStatus(403);
    }

    public function test_cannot_pay_fine_on_loan_without_fine(): void
    {
        $this->freezeTime();

        $librarian = User::factory()->create(['role' => 'librarian']);
        $reader = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($reader, $book);
        $this->travel(5)->days();
        $this->loanService->returnBook($loan->fresh());

        $response = $this->actingAs($librarian)
            ->patchJson("/api/loans/{$loan->id}/pay-fine");

        $response->assertStatus(409);
    }

    public function test_user_can_borrow_again_after_fine_is_paid(): void
    {
        $this->freezeTime();

        $librarian = User::factory()->create(['role' => 'librarian']);
        $reader = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($reader, $book);
        $this->travel(17)->days();
        $this->loanService->returnBook($loan->fresh());

        $this->actingAs($librarian)
            ->patchJson("/api/loans/{$loan->id}/pay-fine");

        $newBook = Book::factory()->create(['available_copies' => 1]);

        $response = $this->actingAs($reader)
            ->postJson('/api/loans', ['book_id' => $newBook->id]);

        $response->assertStatus(201);
    }
}
