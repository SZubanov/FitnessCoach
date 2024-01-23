<?php

namespace App\Http\Requests\Web\Diary;

use App\Dto\Web\Diary\DiarySizesStoreDto;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\WithData;

class DiarySizesStoreRequest extends FormRequest
{
    use WithData;

    public string $dataClass = DiarySizesStoreDto::class;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date' => 'required|date',
            'neck' => 'nullable|numeric',
            'chest' => 'nullable|numeric',
            'waist' => 'nullable|numeric',
            'biceps' => 'nullable|numeric',
            'pelvis' => 'nullable|numeric',
            'thigh' => 'nullable|numeric',
            'tibia' => 'nullable|numeric'
        ];
    }
}
