<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Notifications\LateReturnNotification;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanNotificationTest extends TestCase
{
    use RefreshDatabase;

    private LoanService $loanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loanService = app(LoanService::class);
    }

    public function test_late_return_sends_notification(): void
    {
        Notification::fake();

        $this->freezeTime();

        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($user, $book);

        $this->travel(17)->days();

        $this->loanService->returnBook($loan->fresh());

        Notification::assertSentTo($user, LateReturnNotification::class);
    }

    public function test_on_time_return_does_not_send_notification(): void
    {
        Notification::fake();

        $this->freezeTime();

        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($user, $book);

        $this->travel(10)->days();

        $this->loanService->returnBook($loan->fresh());

        Notification::assertNotSentTo($user, LateReturnNotification::class);
    }

    public function test_notification_contains_fine_amount(): void
    {
        Notification::fake();

        $this->freezeTime();

        $user = User::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = $this->loanService->borrowBook($user, $book);

        $this->travel(17)->days();

        $this->loanService->returnBook($loan->fresh());

        Notification::assertSentTo($user, function (LateReturnNotification $notification) {
            return $notification->fineAmount === 30;
        });
    }
}
