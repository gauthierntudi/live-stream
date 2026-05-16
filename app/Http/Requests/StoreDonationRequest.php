<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency')) {
            $this->merge([
                'currency' => strtoupper((string) $this->input('currency')),
            ]);
        }

        $email = trim((string) $this->input('donor_email', ''));
        if ($email === '' && $this->input('payment_method') !== 'card') {
            $this->merge(['donor_email' => null]);
        }

    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowedMethods = collect(config('payment_methods.methods', []))
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        $isCard = $this->input('payment_method') === 'card';

        return [
            'donor_name' => ['nullable', 'string', 'max:120'],
            'donor_email' => array_merge(
                $isCard ? ['required'] : ['nullable'],
                ['email', 'max:255']
            ),
            'donor_phone' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3', Rule::in(['USD', 'CDF'])],
            'payment_method' => ['required', 'string', Rule::in($allowedMethods)],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
