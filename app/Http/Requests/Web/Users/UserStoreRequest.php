<?php

namespace App\Http\Requests\Web\Users;

use App\Dto\Web\UserStoreDto;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\WithData;

class UserStoreRequest extends FormRequest
{
    use WithData;

    public string $dataClass = UserStoreDto::class;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|min:5',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
            'role' => 'required|integer|exists:roles,id',
        ];

    }
}
