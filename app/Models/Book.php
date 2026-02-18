<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $author
 * @property string $isbn
 * @property int $total_copies
 * @property int $available_copies
 */
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

    protected function casts(): array
    {
        return [
            'total_copies' => 'integer',
            'available_copies' => 'integer',
        ];
    }

    public function isAvailable(): bool
    {
        return $this->available_copies > 0;
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
