<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'cash_balance'];

    protected $casts = [
        'cash_balance' => 'decimal:2'
    ];

    protected function casts(): array
    {
        return [
            'cash_balance' => 'decimal:2'
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

}
