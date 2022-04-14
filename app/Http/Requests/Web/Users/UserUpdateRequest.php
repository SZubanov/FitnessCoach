<?php

namespace App\Http\Requests\Web\Users;

use App\Dto\Web\UserStoreDto;
use App\Dto\Web\UserUpdateDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\WithData;

class UserUpdateRequest extends FormRequest
{
    use WithData;

    public string $dataClass = UserUpdateDto::class;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|min:5',
            'email' => [
                'required',
                Rule::unique('users')->ignore($this->user),
            ],
            'password' => 'nullable|confirmed|min:8',
            'role' => 'required|integer|exists:roles,id',
        ];
    }
}
