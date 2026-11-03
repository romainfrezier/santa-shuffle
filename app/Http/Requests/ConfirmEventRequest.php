<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\SessionAccess;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        (new SessionAccess($this->session()))->authorizeManager($this->route('exchange'), $this->isMethod('DELETE'));

        return true;
    }

    public function rules(): array
    {
        return [
            'confirm' => ['accepted'],
        ];
    }
}
