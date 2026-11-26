<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_crear_editar_y_desactivar_categoria(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin, 'sanctum');

        $created = $this->postJson('/api/categories', [
            'name' => 'Certificados',
            'description' => 'Certificados académicos',
        ])->assertCreated()->assertJsonPath('data.active', true);

        $id = $created->json('data.id');

        $this->patchJson("/api/categories/{$id}", ['name' => 'Certificados académicos'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Certificados académicos');

        $this->patchJson("/api/categories/{$id}/toggle-active")
            ->assertOk()
            ->assertJsonPath('data.active', false);
    }

    public function test_no_admin_no_puede_crear_categoria(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/categories', ['name' => 'Otra'])
            ->assertForbidden();
    }

    public function test_nombre_de_categoria_debe_ser_unico(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Category::create(['name' => 'Matrícula']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/categories', ['name' => 'Matrícula'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_cualquier_rol_autenticado_puede_consultar_categorias(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        Category::create(['name' => 'Becas']);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Becas']);
    }
}
