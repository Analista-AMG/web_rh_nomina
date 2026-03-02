<?php

namespace App\Http\Requests;

use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('personas.edit');
    }

    public function rules(): array
    {
        $persona = $this->route('persona');

        return [
            'tipo_documento'                 => 'required',
            'numero_documento'               => ['required', 'max:20', Rule::unique(Persona::class, 'numero_documento')->ignore($persona)],
            'nombres'                        => 'required|max:255',
            'apellido_paterno'               => 'required|max:255',
            'apellido_materno'               => 'nullable|max:255',
            'fecha_nacimiento'               => ['nullable', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'genero'                         => 'nullable|integer|in:1,2',
            'pais'                           => 'nullable',
            'departamento'                   => 'nullable',
            'provincia'                      => 'nullable',
            'distrito'                       => 'nullable',
            'direccion'                      => 'nullable|max:255',
            'numero_telefonico'              => 'nullable|regex:/^\d{1,9}$/',
            'correo_electronico_personal'    => 'nullable|email|max:255',
            'correo_electronico_corporativo' => 'nullable|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'numero_documento.unique'          => 'Persona ya se encuentra en la base de datos',
            'fecha_nacimiento.before_or_equal' => 'La persona debe ser mayor de 18 años',
            'numero_telefonico.regex'          => 'El número telefónico debe tener máximo 9 dígitos',
        ];
    }
}
