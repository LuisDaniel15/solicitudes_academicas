<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_listar_usuarios(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['name' => 'Funcionario Uno', 'role' => User::ROLE_OFFICER]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Funcionario Uno']);
    }

    public function test_estudiante_no_puede_listar_usuarios(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_admin_puede_crear_usuario_con_rol(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Nuevo Funcionario',
                'email' => 'nuevo@sigesu.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_OFFICER,
            ])
            ->assertCreated()
            ->assertJsonPath('data.role', User::ROLE_OFFICER);

        $this->assertDatabaseHas('users', ['email' => 'nuevo@sigesu.test', 'role' => User::ROLE_OFFICER]);
    }

    public function test_admin_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$admin->id}/toggle-active")
            ->assertForbidden();
    }

    public function test_admin_puede_cambiar_rol_y_desactivar_otro_usuario(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$target->id}", ['role' => User::ROLE_OFFICER])
            ->assertOk()
            ->assertJsonPath('data.role', User::ROLE_OFFICER);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$target->id}/toggle-active")
            ->assertOk()
            ->assertJsonPath('data.active', false);
    }
}
