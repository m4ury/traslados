<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VehiculoRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patente' => ['required', 'string', 'max:20', 'unique:vehiculos,patente'],
            'vin' => ['nullable', 'string', 'max:32', 'unique:vehiculos,vin'],
            'marca_id' => ['required', 'exists:marcas,id'],
            'modelo_id' => ['required', 'exists:modelos,id'],
            'anio' => ['required', 'integer', 'min:1900', 'max:' . (now()->year + 1)],
            'actual_km' => ['required', 'integer', 'min:0'],
        ];
    }
}
