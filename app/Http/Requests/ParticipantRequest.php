<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\SessionAccess;
use Illuminate\Foundation\Http\FormRequest;

class ParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exchange = $this->route('exchange');
        if ($this->routeIs('exchanges.invite')) {
            (new SessionAccess($this->session()))->authorizeManager($exchange);
        }
        $exchange->ensureActive();

        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:254'],
            'wishes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
