<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\SubjectIndexRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\PaginatedCollection;
use App\Http\Resources\SubjectResource;
use App\Services\SubjectService;

class SubjectController extends Controller
{
    public function __construct(
        protected SubjectService $service
    ) {}

    // ✅ Unpack data and meta separately
    public function index(SubjectIndexRequest $request)
    {
        $data = $this->service->all($request->validated());

        return new PaginatedCollection(
            SubjectResource::collection($data['data']), // ← the rows
            $data['meta']                               // ← pagination info
        );
    }

    public function show($id)
    {
        return new SubjectResource(
            $this->service->find($id)
        );
    }

    public function store(StoreSubjectRequest $request)
    {
        $subject = $this->service->create(
            $request->validated()
        );

        return (new SubjectResource($subject))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateSubjectRequest $request, $id)
    {
        $subject = $this->service->update(
            $id,
            $request->validated()
        );

        return new SubjectResource($subject);
    }

    public function destroy($id)
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully'
        ]);
    }
}
