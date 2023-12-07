<?php

namespace App\Http\Requests\Web\Diary;

use App\Dto\Web\Diary\DiaryWeightStoreDto;
use App\Helpers\WeightUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'weight' => 'required|numeric',
            'unit' => [
                'required',
                Rule::in([WeightUnit::G, WeightUnit::LB, WeightUnit::KG, WeightUnit::OZ])
            ]
        ];

    }
}
