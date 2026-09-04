<?php

namespace App\Enums;

enum TransactionType: string
{
    //

    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Buy = 'buy';
    case Sell = 'sell';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::Withdrawal => 'Withdrawal',
            self::Buy => 'Buy',
            self::Sell => 'Sell',
        };
    }

    public function requiresInstrument(): bool
    {
        return in_array($this, [self::Buy, self::Sell],true);
    }
}
