<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Jan Knihovník',
            'email' => 'librarian@library.cz',
            'password' => Hash::make('password'),
            'role' => 'librarian',
        ]);

        User::create([
            'name' => 'Petr Čtenář',
            'email' => 'petr@library.cz',
            'password' => Hash::make('password'),
            'role' => 'reader',
        ]);

        User::create([
            'name' => 'Marie Nová',
            'email' => 'marie@library.cz',
            'password' => Hash::make('password'),
            'role' => 'reader',
        ]);

        Book::create([
            'title' => 'Válka s mloky',
            'author' => 'Karel Čapek',
            'isbn' => '978-80-207-1637-7',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        Book::create([
            'title' => '1984',
            'author' => 'George Orwell',
            'isbn' => '978-80-7335-315-1',
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        Book::create([
            'title' => 'Malý princ',
            'author' => 'Antoine de Saint-Exupéry',
            'isbn' => '978-80-7381-930-8',
            'total_copies' => 1,
            'available_copies' => 0,
        ]);

        Book::create([
            'title' => 'Farma zvířat',
            'author' => 'George Orwell',
            'isbn' => '978-80-7335-316-8',
            'total_copies' => 2,
            'available_copies' => 1,
        ]);

        Book::create([
            'title' => 'Proces',
            'author' => 'Franz Kafka',
            'isbn' => '978-80-7335-317-5',
            'total_copies' => 1,
            'available_copies' => 1,
        ]);
    }
}
