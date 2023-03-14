<?php

namespace App\Http\Requests;

use App\Dto\OAuth1CallbackDto;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\WithData;

class OAuth1CallbackRequest extends FormRequest
{
    use WithData;

    public string $dataClass = OAuth1CallbackDto::class;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'oauth_token' => 'string',
            'oauth_verifier' => 'string',
        ];

    }
}
