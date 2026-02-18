<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_reader_role(): void
    {
        $user = User::factory()->create(['role' => 'reader']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'reader',
        ]);
    }

    public function test_user_can_be_created_with_librarian_role(): void
    {
        $user = User::factory()->create(['role' => 'librarian']);

        $this->assertEquals('librarian', $user->role);
    }

    public function test_user_default_role_is_reader(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('reader', $user->role);
    }

    public function test_user_is_librarian_returns_true_for_librarian(): void
    {
        $user = User::factory()->create(['role' => 'librarian']);

        $this->assertTrue($user->isLibrarian());
    }

    public function test_user_is_librarian_returns_false_for_reader(): void
    {
        $user = User::factory()->create(['role' => 'reader']);

        $this->assertFalse($user->isLibrarian());
    }

    public function test_user_is_reader_returns_true_for_reader(): void
    {
        $user = User::factory()->create(['role' => 'reader']);

        $this->assertTrue($user->isReader());
    }
}
