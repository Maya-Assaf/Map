<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NearbyHexagonRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'hex_size_km' => 'nullable|numeric|min:0.05|max:500',
            'rings' => 'nullable|integer|min:0|max:10',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'type' => 'nullable|string|in:point,path,space,polygon',
            'aspect_id' => 'nullable|integer|exists:aspects,id',
            'sub_aspect_id' => 'nullable|integer|exists:sub_aspects,id',
            'category_id' => 'nullable|integer|exists:categories,id',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude coordinate is required.',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'longitude.required' => 'Longitude coordinate is required.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
            'rings.min' => 'Rings must be at least 0.',
            'rings.max' => 'Maximum allowed rings is 10.',
        ];
    }
}
