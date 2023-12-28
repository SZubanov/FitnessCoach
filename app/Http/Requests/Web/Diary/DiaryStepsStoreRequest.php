<?php

namespace App\Http\Requests\Web\Diary;

use App\Dto\Web\Diary\DiaryStepsStoreDto;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\WithData;

class DiaryStepsStoreRequest extends FormRequest
{
    use WithData;

    public string $dataClass = DiaryStepsStoreDto::class;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date' => 'required|date',
            'steps' => 'required|numeric',
        ];
    }
}
