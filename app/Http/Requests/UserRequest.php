<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($userId)
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($userId)
            ],
            'password' => $isUpdate ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
            'group_id' => 'required|exists:user_groups,id',
            'is_active' => 'required|boolean',
        ];
    }
}
