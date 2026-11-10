<?php

namespace Tests\Feature\Http;

use App\Modulos\Identidad\Enums\EstadoCuenta;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaDatos;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    public function test_cu1_login_redirige_segun_el_rol(): void
    {
        $alumno = $this->usuario();
        $this->post('/login', ['correo' => $alumno->correo, 'password' => 'Secreta123'])->assertRedirect(route('alumno.inicio'));
        $this->post('/logout');

        $coord = $this->usuario(RolSistema::COORDINADOR);
        $this->post('/login', ['correo' => $coord->correo, 'password' => 'Secreta123'])->assertRedirect(route('coordinacion.inicio'));
    }

    public function test_error_de_login_no_revela_que_campo_fallo(): void
    {
        $alumno = $this->usuario();
        $this->post('/login', ['correo' => $alumno->correo, 'password' => 'Mala123'])
            ->assertSessionHasErrors(['correo' => 'Correo o contraseña incorrectos.']);
        $this->post('/login', ['correo' => 'noexiste@alumno.ipn.mx', 'password' => 'Mala123'])
            ->assertSessionHasErrors(['correo' => 'Correo o contraseña incorrectos.']);
        $this->assertGuest();
    }

    public function test_rb12_sugiere_restablecer_tras_tres_fallos(): void
    {
        $alumno = $this->usuario();
        foreach (range(1, 2) as $_) {
            $this->post('/login', ['correo' => $alumno->correo, 'password' => 'Mala123'])->assertSessionMissing('sugerir_restablecer', true);
        }
        $this->post('/login', ['correo' => $alumno->correo, 'password' => 'Mala123'])->assertSessionHas('sugerir_restablecer', true);
    }

    public function test_rb24_bloqueo_tras_cinco_intentos(): void
    {
        $alumno = $this->usuario();
        foreach (range(1, 5) as $_) {
            $this->post('/login', ['correo' => $alumno->correo, 'password' => 'Mala123']);
        }
        // Aun con la contraseña correcta, el sexto intento se rechaza
        $this->post('/login', ['correo' => $alumno->correo, 'password' => 'Secreta123'])
            ->assertSessionHasErrors('correo');
        $this->assertGuest();
    }

    public function test_cuenta_suspendida_no_puede_entrar(): void
    {
        $alumno = $this->usuario();
        $alumno->update(['estado' => EstadoCuenta::SUSPENDIDA]);
        $this->post('/login', ['correo' => $alumno->correo, 'password' => 'Secreta123'])
            ->assertSessionHasErrors(['correo' => 'Su cuenta está suspendida, contacte a la coordinación.']);
        $this->assertGuest();
    }

    public function test_cu2_registro_valido_crea_alumno(): void
    {
        $this->post('/registro', [
            'matricula' => '2024630999', 'nombre' => 'Pedro', 'apellidos' => 'Sánchez Ruiz',
            'correo' => 'PSanchez@Alumno.IPN.mx', 'password' => 'Segura2026', 'password_confirmation' => 'Segura2026',
            'aviso_privacidad' => '1',
        ])->assertRedirect(route('alumno.inicio'));

        $u = Usuario::where('matricula', '2024630999')->first();
        $this->assertSame('psanchez@alumno.ipn.mx', $u->correo);
        $this->assertTrue($u->esAlumno());
        $this->assertNotSame('Segura2026', $u->password);   // se guarda con hash
    }

    public function test_rb1_rb2_rb3_registro_invalido(): void
    {
        $this->post('/registro', [
            'matricula' => '12345', 'nombre' => 'Pedro', 'apellidos' => 'Sánchez',
            'correo' => 'pedro@ipn.mx', 'password' => 'debil', 'password_confirmation' => 'debil',
        ])->assertSessionHasErrors(['matricula', 'correo', 'password', 'aviso_privacidad']);
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_rbac_alumno_no_entra_a_coordinacion(): void
    {
        $this->actingAs($this->usuario())->get('/coordinacion')->assertForbidden();
    }

    public function test_superadministrador_hereda_acceso_de_coordinador(): void
    {
        $this->actingAs($this->usuario(RolSistema::SUPERADMINISTRADOR))->get('/coordinacion')->assertOk();
    }

    public function test_coordinador_no_ve_pantallas_del_alumno(): void
    {
        $this->actingAs($this->usuario(RolSistema::COORDINADOR))->get('/actividades')->assertForbidden();
    }
}
