<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_all_books(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/books');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_show_single_book(): void
    {
        $book = Book::factory()->create(['title' => 'Clean Code']);

        $response = $this->getJson("/api/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Clean Code']);
    }

    public function test_show_returns_404_for_nonexistent_book(): void
    {
        $response = $this->getJson('/api/books/999');

        $response->assertStatus(404);
    }

    public function test_librarian_can_create_book(): void
    {
        $librarian = User::factory()->create(['role' => 'librarian']);

        $response = $this->actingAs($librarian)
            ->postJson('/api/books', [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '978-0132350884',
                'total_copies' => 3,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Clean Code']);

        $this->assertDatabaseHas('books', ['isbn' => '978-0132350884']);
    }

    public function test_reader_cannot_create_book(): void
    {
        $reader = User::factory()->create(['role' => 'reader']);

        $response = $this->actingAs($reader)
            ->postJson('/api/books', [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '978-0132350884',
                'total_copies' => 3,
            ]);

        $response->assertStatus(403);
    }

    public function test_create_book_validates_required_fields(): void
    {
        $librarian = User::factory()->create(['role' => 'librarian']);

        $response = $this->actingAs($librarian)
            ->postJson('/api/books', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'author', 'isbn', 'total_copies']);
    }

    public function test_create_book_rejects_duplicate_isbn(): void
    {
        $librarian = User::factory()->create(['role' => 'librarian']);
        Book::factory()->create(['isbn' => '978-0132350884']);

        $response = $this->actingAs($librarian)
            ->postJson('/api/books', [
                'title' => 'Another Book',
                'author' => 'Author',
                'isbn' => '978-0132350884',
                'total_copies' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_librarian_can_update_book(): void
    {
        $librarian = User::factory()->create(['role' => 'librarian']);
        $book = Book::factory()->create();

        $response = $this->actingAs($librarian)
            ->putJson("/api/books/{$book->id}", [
                'title' => 'Updated Title',
                'author' => $book->author,
                'isbn' => $book->isbn,
                'total_copies' => $book->total_copies,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    public function test_librarian_can_delete_book(): void
    {
        $librarian = User::factory()->create(['role' => 'librarian']);
        $book = Book::factory()->create();

        $response = $this->actingAs($librarian)
            ->deleteJson("/api/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }
}
