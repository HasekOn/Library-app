<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $book_id
 * @property string $status
 * @property Carbon $borrowed_at
 * @property Carbon $due_at
 * @property Carbon|null $returned_at
 * @property int $fine_amount
 * @property boolean $fine_paid
 * @property-read User $user
 * @property-read Book $book
 *
 * @method static Builder active()
 * @method static Builder withUnpaidFines()
 */
class Loan extends Model
{
    use HasFactory;

    public const string STATUS_BORROWED = 'borrowed';
    public const string STATUS_RETURNED = 'returned';

    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'borrowed_at',
        'due_at',
        'returned_at',
        'fine_amount',
        'fine_paid',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
            'fine_amount' => 'integer',
            'fine_paid' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function isBorrowed(): bool
    {
        return $this->status === self::STATUS_BORROWED;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_BORROWED);
    }

    public function scopeWithUnpaidFines($query)
    {
        return $query->where('status', self::STATUS_RETURNED)
            ->where('fine_amount', '>', 0)
            ->where('fine_paid', false);
    }
}
