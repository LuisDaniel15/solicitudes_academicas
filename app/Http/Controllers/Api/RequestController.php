<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRequestRequest;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Requests\CreateRequestRequest;
use App\Http\Requests\RequestIndexRequest;
use App\Http\Requests\StoreObservationRequest;
use App\Http\Resources\ObservationResource;
use App\Http\Resources\RequestResource;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\RequestService;
use App\Services\RequestStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequestController extends Controller
{
    public function __construct(
        private readonly RequestService $requests,
        private readonly AssignmentService $assignments,
        private readonly RequestStateService $states,
    ) {}

    public function index(RequestIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SupportRequest::class);

        /** @var User $user */
        $user = $request->user();
        $filters = $request->filters();

        $query = SupportRequest::query()
            ->with(['category', 'applicant', 'assignee']);

        if ($user->hasRole(User::ROLE_STUDENT)) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole(User::ROLE_OFFICER)) {
            $query->where('assigned_to', $user->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q
                ->where('code', 'like', $search)
                ->orWhere('description', 'like', $search));
        }

        return RequestResource::collection($query->latest()->get());
    }

    public function store(CreateRequestRequest $request): JsonResponse
    {
        $this->authorize('create', SupportRequest::class);

        $solicitud = $this->requests->create($request->user(), $request->validated());

        return (new RequestResource($solicitud))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(SupportRequest $solicitud): RequestResource
    {
        $this->authorize('view', $solicitud);

        $solicitud->load([
            'category', 'applicant', 'assignee',
            'statusHistory.changedBy',
            'observations.user',
            'assignments.previousAssignee', 'assignments.newAssignee', 'assignments.assignedBy',
        ]);

        return new RequestResource($solicitud);
    }

    public function assign(AssignRequestRequest $request, SupportRequest $solicitud): RequestResource
    {
        $this->authorize('assign', $solicitud);

        $officer = User::findOrFail($request->validated('assigned_to'));
        $solicitud = $this->assignments->assign($solicitud, $officer, $request->user());

        return new RequestResource($solicitud->load(['category', 'applicant', 'assignee']));
    }

    public function changeStatus(ChangeStatusRequest $request, SupportRequest $solicitud): RequestResource
    {
        $this->authorize('changeStatus', $solicitud);

        $solicitud = $this->states->change(
            $solicitud,
            $request->validated('status'),
            $request->user(),
            $request->validated('note'),
        );

        return new RequestResource($solicitud->load(['category', 'applicant', 'assignee']));
    }

    public function addObservation(StoreObservationRequest $request, SupportRequest $solicitud): JsonResponse
    {
        $this->authorize('addObservation', $solicitud);

        $observation = $this->states->addObservation(
            $solicitud,
            $request->user(),
            $request->validated('body'),
        );

        return (new ObservationResource($observation->load('user')))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
