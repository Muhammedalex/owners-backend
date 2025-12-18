<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ManageUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // التفويض الفعلي سيتم عبر الـ Policy في الكنترولر
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'distinct'],
        ];
    }
}

?>


