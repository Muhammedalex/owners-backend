<?php

namespace App\Http\Requests\V1\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', Rule::in(['info', 'success', 'warning', 'error', 'system'])],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'data' => ['nullable', 'array'],
            'action_url' => ['nullable', 'string', 'max:500'],
            'action_text' => ['nullable', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'integer', Rule::in([0, 1, 2])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}

