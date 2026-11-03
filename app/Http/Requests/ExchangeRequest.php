<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Exchange;
use App\Services\SessionAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exchange = $this->route('exchange');
        if ($exchange instanceof Exchange) {
            (new SessionAccess($this->session()))->authorizeManager($exchange);
        }

        return is_string($this->session()->get('organizer_email')) && $this->session()->get('organizer_email') !== '';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'gift_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addYear()->format('Y-m-d')],
            'budget' => ['required', 'integer', 'min:1', 'max:10000'],
            'currency' => ['sometimes', 'required', Rule::in(array_keys(Exchange::CURRENCIES))],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
