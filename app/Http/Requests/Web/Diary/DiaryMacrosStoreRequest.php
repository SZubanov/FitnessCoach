<?php

namespace App\Http\Requests\Web\Diary;

use App\Dto\Web\Diary\DiaryMacrosStoreDto;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\WithData;

class DiaryMacrosStoreRequest extends FormRequest
{
    use WithData;

    public string $dataClass = DiaryMacrosStoreDto::class;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date' => 'required|date',
            'kcal' => 'required|numeric',
            'protein' => 'required|numeric',
            'fat' => 'required|numeric',
            'carbs' => 'required|numeric',
        ];

    }
}
