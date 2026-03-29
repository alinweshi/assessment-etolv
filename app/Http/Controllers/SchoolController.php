<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Services\SchoolService;

class SchoolController extends Controller
{
    public function __construct(
        protected SchoolService $service
    ) {}

    public function index()
    {
        return SchoolResource::collection(
            $this->service->all()
        );
    }

    public function show($id)
    {
        return new SchoolResource(
            $this->service->find($id)
        );
    }

    public function store(StoreSchoolRequest $request)
    {
        $school = $this->service->create(
            $request->validated()
        );

        return (new SchoolResource($school))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateSchoolRequest $request, $id)
    {
        $school = $this->service->update(
            $id,
            $request->validated()
        );

        return new SchoolResource($school);
    }

    public function destroy($id)
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'School deleted successfully'
        ]);
    }
}
