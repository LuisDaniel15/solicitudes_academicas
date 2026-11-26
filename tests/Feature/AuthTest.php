<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_devuelve_token_y_usuario(): void
    {
        $user = User::factory()->create([
            'email' => 'login@sigesu.test',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', 'estudiante')
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_login_rechaza_credenciales_incorrectas(): void
    {
        User::factory()->create(['email' => 'login@sigesu.test', 'password' => 'password123']);

        $this->postJson('/api/login', [
            'email' => 'login@sigesu.test',
            'password' => 'incorrecta',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_rechaza_usuario_desactivado(): void
    {
        User::factory()->create([
            'email' => 'inactivo@sigesu.test',
            'password' => 'password123',
            'active' => false,
        ]);

        $this->postJson('/api/login', [
            'email' => 'inactivo@sigesu.test',
            'password' => 'password123',
        ])->assertForbidden();
    }

    public function test_me_requiere_autenticacion(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_logout_revoca_el_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Cada petición HTTP real usa una instancia nueva de la app; en tests hay que
        // limpiar la caché del guard para simularla.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_usuario_desactivado_no_puede_usar_su_token(): void
    {
        $user = User::factory()->create(['active' => false]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')->assertForbidden();
    }
}
