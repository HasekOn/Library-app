<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'total_copies',
        'available_copies',
    ];

    public function isAvailable(): bool
    {
        return $this->available_copies > 0;
    }
}
