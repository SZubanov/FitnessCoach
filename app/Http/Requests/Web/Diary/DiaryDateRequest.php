<?php

namespace App\Http\Requests\Web\Diary;

use Illuminate\Foundation\Http\FormRequest;

class DiaryDateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => 'required|date',
        ];
    }
}
