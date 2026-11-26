<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Observation;
use App\Models\StatusHistory;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private function category(bool $active = true): Category
    {
        return Category::create(['name' => 'Matrícula', 'active' => $active]);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function requestData(Category $category): array
    {
        return [
            'category_id' => $category->id,
            'description' => 'Necesito corregir mi matrícula del semestre.',
            'priority' => 'high',
        ];
    }

    public function test_estudiante_registra_solicitud_con_codigo_y_historial_inicial(): void
    {
        $student = $this->user(User::ROLE_STUDENT);
        $category = $this->category();

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/requests', $this->requestData($category))
            ->assertCreated()
            ->assertJsonPath('data.status', SupportRequest::STATUS_REGISTERED)
            ->assertJsonPath('data.priority', 'high');

        $this->assertMatchesRegularExpression('/^SOL-\d{4}-\d{6}$/', $response->json('data.code'));

        $solicitud = SupportRequest::first();
        $this->assertDatabaseHas('request_status_history', [
            'request_id' => $solicitud->id,
            'from_status' => null,
            'to_status' => SupportRequest::STATUS_REGISTERED,
            'changed_by' => $student->id,
        ]);
    }

    public function test_no_se_puede_registrar_con_categoria_inactiva(): void
    {
        $student = $this->user(User::ROLE_STUDENT);
        $category = $this->category(false);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/requests', $this->requestData($category))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_estudiante_solo_ve_sus_propias_solicitudes(): void
    {
        $student = $this->user(User::ROLE_STUDENT);
        $other = $this->user(User::ROLE_STUDENT);
        $category = $this->category();

        $own = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $student->id,
            'category_id' => $category->id, 'description' => 'Solicitud propia',
        ]);
        $ajena = SupportRequest::create([
            'code' => 'SOL-2026-000002', 'user_id' => $other->id,
            'category_id' => $category->id, 'description' => 'Solicitud ajena',
        ]);

        $response = $this->actingAs($student, 'sanctum')->getJson('/api/requests')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($own->id, $response->json('data.0.id'));
        $this->getJson("/api/requests/{$own->id}")->assertOk();
        $this->getJson("/api/requests/{$ajena->id}")->assertForbidden();
    }

    public function test_funcionario_solo_ve_solicitudes_asignadas(): void
    {
        $officer = $this->user(User::ROLE_OFFICER);
        $category = $this->category();
        $applicant = $this->user(User::ROLE_STUDENT);

        $assigned = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $applicant->id, 'category_id' => $category->id,
            'description' => 'Asignada', 'status' => SupportRequest::STATUS_ASSIGNED, 'assigned_to' => $officer->id,
        ]);
        SupportRequest::create([
            'code' => 'SOL-2026-000002', 'user_id' => $applicant->id, 'category_id' => $category->id,
            'description' => 'Sin asignar',
        ]);

        $response = $this->actingAs($officer, 'sanctum')->getJson('/api/requests')->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($assigned->id, $response->json('data.0.id'));
    }

    public function test_supervisor_asigna_y_se_registra_historial_y_notificacion(): void
    {
        $supervisor = $this->user(User::ROLE_SUPERVISOR);
        $officer = $this->user(User::ROLE_OFFICER);
        $category = $this->category();
        $solicitud = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $this->user(User::ROLE_STUDENT)->id,
            'category_id' => $category->id, 'description' => 'Solicitud a asignar',
        ]);

        $this->actingAs($supervisor, 'sanctum')
            ->postJson("/api/requests/{$solicitud->id}/assignments", ['assigned_to' => $officer->id])
            ->assertOk()
            ->assertJsonPath('data.status', SupportRequest::STATUS_ASSIGNED)
            ->assertJsonPath('data.assigned_to', $officer->id);

        $this->assertDatabaseHas('request_assignments', [
            'request_id' => $solicitud->id,
            'previous_assignee_id' => null,
            'new_assignee_id' => $officer->id,
            'assigned_by' => $supervisor->id,
        ]);
        $this->assertDatabaseHas('request_status_history', [
            'request_id' => $solicitud->id,
            'from_status' => SupportRequest::STATUS_REGISTERED,
            'to_status' => SupportRequest::STATUS_ASSIGNED,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $officer->id,
            'type' => 'request_assigned',
        ]);
    }

    public function test_estudiante_no_puede_asignar(): void
    {
        $student = $this->user(User::ROLE_STUDENT);
        $officer = $this->user(User::ROLE_OFFICER);
        $solicitud = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $student->id,
            'category_id' => $this->category()->id, 'description' => 'Solicitud',
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/requests/{$solicitud->id}/assignments", ['assigned_to' => $officer->id])
            ->assertForbidden();
    }

    public function test_funcionario_atiende_y_resuelve_con_solucion(): void
    {
        $officer = $this->user(User::ROLE_OFFICER);
        $student = $this->user(User::ROLE_STUDENT);
        $supervisor = $this->user(User::ROLE_SUPERVISOR);
        $solicitud = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $student->id,
            'category_id' => $this->category()->id, 'description' => 'Solicitud',
            'status' => SupportRequest::STATUS_ASSIGNED, 'assigned_to' => $officer->id,
        ]);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/requests/{$solicitud->id}/status", [
                'status' => SupportRequest::STATUS_IN_PROGRESS,
            ])->assertOk()->assertJsonPath('data.status', SupportRequest::STATUS_IN_PROGRESS);

        $this->patchJson("/api/requests/{$solicitud->id}/status", [
            'status' => SupportRequest::STATUS_RESOLVED,
            'note' => 'Se corrigió la matrícula en el sistema.',
        ])->assertOk()->assertJsonPath('data.status', SupportRequest::STATUS_RESOLVED);

        $this->assertDatabaseHas('request_observations', [
            'request_id' => $solicitud->id,
            'kind' => Observation::KIND_SOLUTION,
        ]);
        $this->assertEquals(2, StatusHistory::where('request_id', $solicitud->id)->count());
        $this->assertDatabaseHas('notifications', ['user_id' => $student->id, 'type' => 'status_changed']);
        $this->assertDatabaseHas('notifications', ['user_id' => $supervisor->id, 'type' => 'request_resolved']);
    }

    public function test_resolver_sin_solucion_es_rechazado(): void
    {
        $officer = $this->user(User::ROLE_OFFICER);
        $this->user(User::ROLE_SUPERVISOR);
        $solicitud = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $this->user(User::ROLE_STUDENT)->id,
            'category_id' => $this->category()->id, 'description' => 'Solicitud',
            'status' => SupportRequest::STATUS_IN_PROGRESS, 'assigned_to' => $officer->id,
        ]);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/requests/{$solicitud->id}/status", ['status' => SupportRequest::STATUS_RESOLVED])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');
    }

    public function test_transiciones_no_permitidas_son_rechazadas(): void
    {
        $supervisor = $this->user(User::ROLE_SUPERVISOR);
        $solicitud = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $this->user(User::ROLE_STUDENT)->id,
            'category_id' => $this->category()->id, 'description' => 'Solicitud',
        ]);

        $this->actingAs($supervisor, 'sanctum')
            ->patchJson("/api/requests/{$solicitud->id}/status", ['status' => SupportRequest::STATUS_CLOSED])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_cierre_y_reapertura_requieren_supervisor_y_justificacion(): void
    {
        $supervisor = $this->user(User::ROLE_SUPERVISOR);
        $officer = $this->user(User::ROLE_OFFICER);
        $solicitud = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $this->user(User::ROLE_STUDENT)->id,
            'category_id' => $this->category()->id, 'description' => 'Solicitud',
            'status' => SupportRequest::STATUS_RESOLVED, 'assigned_to' => $officer->id,
        ]);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/requests/{$solicitud->id}/status", ['status' => SupportRequest::STATUS_CLOSED])
            ->assertUnprocessable();

        $this->actingAs($supervisor, 'sanctum')
            ->patchJson("/api/requests/{$solicitud->id}/status", [
                'status' => SupportRequest::STATUS_CLOSED, 'note' => 'Revisado y conforme.',
            ])->assertOk()->assertJsonPath('data.status', SupportRequest::STATUS_CLOSED);

        $this->actingAs($officer, 'sanctum')
            ->postJson("/api/requests/{$solicitud->id}/observations", ['body' => 'No debería permitirse'])
            ->assertUnprocessable();

        $this->actingAs($supervisor, 'sanctum')
            ->patchJson("/api/requests/{$solicitud->id}/status", ['status' => SupportRequest::STATUS_REOPENED])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');

        $this->patchJson("/api/requests/{$solicitud->id}/status", [
            'status' => SupportRequest::STATUS_REOPENED, 'note' => 'El usuario aportó nueva evidencia.',
        ])->assertOk()->assertJsonPath('data.status', SupportRequest::STATUS_REOPENED);

        $this->assertDatabaseHas('request_observations', ['kind' => Observation::KIND_REOPEN]);
    }

    public function test_rechazo_requiere_motivo(): void
    {
        $supervisor = $this->user(User::ROLE_SUPERVISOR);
        $solicitud = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $this->user(User::ROLE_STUDENT)->id,
            'category_id' => $this->category()->id, 'description' => 'Solicitud',
        ]);

        $this->actingAs($supervisor, 'sanctum')
            ->patchJson("/api/requests/{$solicitud->id}/status", ['status' => SupportRequest::STATUS_REJECTED])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');

        $this->patchJson("/api/requests/{$solicitud->id}/status", [
            'status' => SupportRequest::STATUS_REJECTED, 'note' => 'No cumple los requisitos.',
        ])->assertOk();

        $this->assertDatabaseHas('request_observations', ['kind' => Observation::KIND_REJECTION]);
    }

    public function test_funcionario_no_puede_atender_solicitud_no_asignada(): void
    {
        $officer = $this->user(User::ROLE_OFFICER);
        $solicitud = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $this->user(User::ROLE_STUDENT)->id,
            'category_id' => $this->category()->id, 'description' => 'Solicitud ajena',
            'status' => SupportRequest::STATUS_ASSIGNED, 'assigned_to' => $this->user(User::ROLE_OFFICER)->id,
        ]);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/requests/{$solicitud->id}/status", ['status' => SupportRequest::STATUS_IN_PROGRESS])
            ->assertForbidden();
    }

    public function test_detalle_incluye_historial_observaciones_y_asignaciones(): void
    {
        $supervisor = $this->user(User::ROLE_SUPERVISOR);
        $officer = $this->user(User::ROLE_OFFICER);
        $solicitud = SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $this->user(User::ROLE_STUDENT)->id,
            'category_id' => $this->category()->id, 'description' => 'Solicitud',
        ]);

        $this->actingAs($supervisor, 'sanctum')
            ->postJson("/api/requests/{$solicitud->id}/assignments", ['assigned_to' => $officer->id])
            ->assertOk();

        $response = $this->getJson("/api/requests/{$solicitud->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'code', 'status', 'status_history', 'observations', 'assignments',
                ],
            ]);

        $this->assertCount(1, $response->json('data.status_history'));
        $this->assertCount(1, $response->json('data.assignments'));
        $this->assertEquals(1, Assignment::count());
        $this->assertGreaterThanOrEqual(1, Notification::count());
    }

    public function test_reporte_y_notificaciones_por_rol(): void
    {
        $supervisor = $this->user(User::ROLE_SUPERVISOR);
        $student = $this->user(User::ROLE_STUDENT);
        $category = $this->category();

        SupportRequest::create([
            'code' => 'SOL-2026-000001', 'user_id' => $student->id,
            'category_id' => $category->id, 'description' => 'Solicitud',
        ]);
        Notification::create([
            'user_id' => $student->id, 'type' => 'status_changed',
            'title' => 'Cambio', 'body' => 'Tu solicitud cambió',
        ]);

        $this->actingAs($supervisor, 'sanctum')
            ->getJson('/api/reports/summary')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('by_status.REGISTRADA', 1);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $id = Notification::first()->id;
        $this->patchJson("/api/notifications/{$id}/read")->assertOk()->assertJsonStructure(['data' => ['read_at']]);
        $this->patchJson('/api/notifications/read-all')->assertOk();
        $this->assertNotNull(Notification::first()->read_at);
    }
}
