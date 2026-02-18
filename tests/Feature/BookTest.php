<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_can_be_created_with_required_attributes(): void
    {
        $book = Book::create([
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '978-0132350884',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        $this->assertDatabaseHas('books', [
            'title' => 'Clean Code',
            'isbn' => '978-0132350884',
        ]);
        $this->assertEquals(3, $book->total_copies);
        $this->assertEquals(3, $book->available_copies);
    }

    public function test_book_is_available_when_copies_exist(): void
    {
        $book = Book::factory()->create(['available_copies' => 2]);

        $this->assertTrue($book->isAvailable());
    }

    public function test_book_is_not_available_when_no_copies(): void
    {
        $book = Book::factory()->create(['available_copies' => 0]);

        $this->assertFalse($book->isAvailable());
    }
}
