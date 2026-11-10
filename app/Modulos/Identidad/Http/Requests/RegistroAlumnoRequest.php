<?php

namespace App\Modulos\Identidad\Http\Requests;

use App\Modulos\Identidad\Reglas\BoletaIpn;
use App\Modulos\Identidad\Reglas\CorreoAlumnoIpn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** Entrada E1 — Registro de alumno (RB1, RB2, RB3). */
class RegistroAlumnoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'correo' => mb_strtolower(trim((string) $this->correo)),
            'matricula' => preg_replace('/\s+/', '', (string) $this->matricula),
            'nombre' => trim((string) $this->nombre),
            'apellidos' => trim((string) $this->apellidos),
        ]);
    }

    public function rules(): array
    {
        return [
            'matricula' => ['required', new BoletaIpn, 'unique:usuarios,matricula'],
            'nombre' => ['required', 'string', 'min:2', 'max:60', "regex:/^[\pL\s'.-]+$/u"],
            'apellidos' => ['required', 'string', 'min:2', 'max:80', "regex:/^[\pL\s'.-]+$/u"],
            'correo' => ['required', 'string', 'max:120', new CorreoAlumnoIpn, 'unique:usuarios,correo'],
            'password' => ['required', 'confirmed', 'max:64', Password::min(8)->mixedCase()->numbers()],
            'aviso_privacidad' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'matricula.unique' => 'Esta boleta ya está registrada.',
            'correo.unique' => 'Este correo ya está registrado.',
            'nombre.regex' => 'El nombre solo puede contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
            'aviso_privacidad.accepted' => 'Debe aceptar el aviso de privacidad.',
        ];
    }

    public function attributes(): array
    {
        return ['matricula' => 'boleta', 'password' => 'contraseña'];
    }
}
