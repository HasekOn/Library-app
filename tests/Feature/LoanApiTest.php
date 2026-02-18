<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_borrow_book_via_api(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 2]);

        $response = $this->actingAs($user)
            ->postJson('/api/loans', ['book_id' => $book->id]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'borrowed']);

        $this->assertEquals(1, $book->fresh()->available_copies);
    }

    public function test_borrow_returns_error_when_no_copies(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $response = $this->actingAs($user)
            ->postJson('/api/loans', ['book_id' => $book->id]);

        $response->assertStatus(409);
    }

    public function test_borrow_returns_error_when_max_loans_reached(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $book = Book::factory()->create(['available_copies' => 1]);
            $this->actingAs($user)->postJson('/api/loans', ['book_id' => $book->id]);
        }

        $extraBook = Book::factory()->create(['available_copies' => 1]);

        $response = $this->actingAs($user)
            ->postJson('/api/loans', ['book_id' => $extraBook->id]);

        $response->assertStatus(409);
    }

    public function test_user_can_return_book_via_api(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $borrowResponse = $this->actingAs($user)
            ->postJson('/api/loans', ['book_id' => $book->id]);

        $loanId = $borrowResponse->json('data.id');

        $response = $this->actingAs($user)
            ->patchJson("/api/loans/{$loanId}/return");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'returned']);
    }

    public function test_cannot_return_already_returned_loan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $borrowResponse = $this->actingAs($user)
            ->postJson('/api/loans', ['book_id' => $book->id]);

        $loanId = $borrowResponse->json('data.id');

        $this->actingAs($user)->patchJson("/api/loans/{$loanId}/return");

        $response = $this->actingAs($user)
            ->patchJson("/api/loans/{$loanId}/return");

        $response->assertStatus(409);
    }

    public function test_can_list_user_loans(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 2]);

        $this->actingAs($user)->postJson('/api/loans', ['book_id' => $book->id]);

        $response = $this->actingAs($user)->getJson('/api/loans');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_user_cannot_borrow(): void
    {
        $book = Book::factory()->create(['available_copies' => 1]);

        $response = $this->postJson('/api/loans', ['book_id' => $book->id]);

        $response->assertStatus(401);
    }
}
