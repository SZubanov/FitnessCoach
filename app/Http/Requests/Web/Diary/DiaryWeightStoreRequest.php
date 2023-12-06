<?php

namespace App\Http\Requests\Web\Diary;

use App\Dto\Web\Diary\DiaryWeightStoreDto;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\WithData;

class DiaryWeightStoreRequest extends FormRequest
{
    use WithData;

    public string $dataClass = DiaryWeightStoreDto::class;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date' => 'required|date',
            'weight' => 'required|numeric'
        ];

    }
}
