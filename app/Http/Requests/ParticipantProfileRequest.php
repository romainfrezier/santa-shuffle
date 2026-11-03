<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\SessionAccess;
use Illuminate\Foundation\Http\FormRequest;

class ParticipantProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        (new SessionAccess($this->session()))->participant($this->route('exchange'));

        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'wishes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
