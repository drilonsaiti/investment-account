<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => [
                Rule::requiredIf(fn() => !$this->typeRequiresInstrument()),
                Rule::prohibitedIf(fn() => $this->typeRequiresInstrument()),
                'nullable', 'numeric', 'gt:0', 'decimal:0,2',
            ],
            'instrument' => [
                Rule::requiredIf(fn() => $this->typeRequiresInstrument()),
                Rule::prohibitedIf(fn() => !$this->typeRequiresInstrument()),
                'nullable', 'string', 'max:20'
            ],
            'quantity' => [
                Rule::requiredIf(fn() => $this->typeRequiresInstrument()),
                Rule::prohibitedIf(fn() => !$this->typeRequiresInstrument()),
                'nullable', 'integer', 'min:1',
            ],
            'price_per_unit' => [
                Rule::requiredIf(fn() => $this->typeRequiresInstrument()),
                Rule::prohibitedIf(fn() => !$this->typeRequiresInstrument()),
                'nullable', 'numeric', 'gt:0', 'decimal:0,4',
            ],
        ];
    }

    private function typeRequiresInstrument(): bool
    {
        $type = $this->input('type');

        if (!is_string($type)) {
            return false;
        }

        return TransactionType::tryFrom($type)?->requiresInstrument() ?? false;
    }
}
