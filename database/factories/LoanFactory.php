<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => Loan::STATUS_BORROWED,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(14),
            'returned_at' => null,
        ];
    }
}
