<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    // We don't need timestamps for this model because is immutable, so we need only 'created_at'
    // so we don't need to update a transaction
    const UPDATED_AT = null;

    protected $fillable = [
        'client_id', 'type', 'amount', 'instrument', 'quantity', 'price_per_unit',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'amount' => 'decimal:2',
        'price_per_unit' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeHoldings(Builder $query): Builder
    {
        return $query
            ->whereNotNull('instrument')
            ->selectRaw("
            instrument,
            SUM(
                CASE
                    WHEN type = 'buy' THEN quantity
                    WHEN type = 'sell' THEN -quantity
                    ELSE 0
                END
            ) as net_quantity
        ")
            ->groupBy('instrument');
    }
}
