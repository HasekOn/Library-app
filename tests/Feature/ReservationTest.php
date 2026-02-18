<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use App\Exceptions\BookAvailableForLoanException;
use App\Exceptions\InvalidReservationStateException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private ReservationService $reservationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reservationService = app(ReservationService::class);
    }

    public function test_user_can_reserve_unavailable_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $reservation = $this->reservationService->reserveBook($user, $book);

        $this->assertNotNull($reservation);
        $this->assertEquals('active', $reservation->status);
        $this->assertNotNull($reservation->expires_at);
    }

    public function test_cannot_reserve_book_that_is_available(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 2]);

        $this->expectException(BookAvailableForLoanException::class);

        $this->reservationService->reserveBook($user, $book);
    }

    public function test_reservation_expires_after_three_days(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $reservation = $this->reservationService->reserveBook($user, $book);

        $this->assertTrue(
            $reservation->expires_at->equalTo(now()->addDays(3))
        );
    }

    public function test_user_can_cancel_active_reservation(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $reservation = $this->reservationService->reserveBook($user, $book);
        $cancelled = $this->reservationService->cancelReservation($reservation);

        $this->assertEquals('cancelled', $cancelled->status);
    }

    public function test_cannot_cancel_already_cancelled_reservation(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $reservation = $this->reservationService->reserveBook($user, $book);
        $this->reservationService->cancelReservation($reservation);

        $this->expectException(InvalidReservationStateException::class);

        $this->reservationService->cancelReservation($reservation->fresh());
    }

    public function test_cannot_cancel_fulfilled_reservation(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 0]);

        $reservation = $this->reservationService->reserveBook($user, $book);
        $reservation->update(['status' => 'fulfilled']);

        $this->expectException(InvalidReservationStateException::class);

        $this->reservationService->cancelReservation($reservation->fresh());
    }
}
