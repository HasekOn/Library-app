<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reserve_unavailable_book_via_api(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $response = $this->actingAs($user)
            ->postJson('/api/reservations', ['book_id' => $book->id]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'active']);
    }

    public function test_cannot_reserve_available_book_via_api(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 2]);

        $response = $this->actingAs($user)
            ->postJson('/api/reservations', ['book_id' => $book->id]);

        $response->assertStatus(409);
    }

    public function test_user_can_cancel_reservation_via_api(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $createResponse = $this->actingAs($user)
            ->postJson('/api/reservations', ['book_id' => $book->id]);

        $reservationId = $createResponse->json('data.id');

        $response = $this->actingAs($user)
            ->patchJson("/api/reservations/{$reservationId}/cancel");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'cancelled']);
    }

    public function test_cannot_cancel_already_cancelled_reservation_via_api(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $createResponse = $this->actingAs($user)
            ->postJson('/api/reservations', ['book_id' => $book->id]);

        $reservationId = $createResponse->json('data.id');

        $this->actingAs($user)->patchJson("/api/reservations/{$reservationId}/cancel");

        $response = $this->actingAs($user)
            ->patchJson("/api/reservations/{$reservationId}/cancel");

        $response->assertStatus(409);
    }

    public function test_can_list_user_reservations(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $this->actingAs($user)
            ->postJson('/api/reservations', ['book_id' => $book->id]);

        $response = $this->actingAs($user)->getJson('/api/reservations');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
