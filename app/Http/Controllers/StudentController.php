<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollStudentRequest;
use App\Http\Requests\IndexRequest;
use App\Http\Requests\RegisterSubjectRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Services\StudentService;

class StudentController extends Controller
{
    public function __construct(
        protected StudentService $service
    ) {}

    /**
     * Get all students
     */
    public function index(IndexRequest $request)
    {
        $data = $this->service->all($request->validated());

        return StudentResource::collection($data['data'])
            ->additional([
                'meta' => $data['meta']
            ]);
    }
    /**
     * Get single student
     */
    public function show($id)
    {
        return new StudentResource(
            $this->service->find($id)
        );
    }

    /**
     * Create student
     */
    public function store(StoreStudentRequest $request)
    {
        $student = $this->service->create(
            $request->validated()
        );

        return (new StudentResource($student))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update student
     */
    public function update(UpdateStudentRequest $request, $id)
    {
        return new StudentResource(
            $this->service->update($id, $request->validated())
        );
    }

    /**
     * Delete student
     */
    public function destroy($id)
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    }
    /**
     * Enroll student in school
     */

    public function enroll(EnrollStudentRequest $request, string $student)
    {
        $data = $this->service->enrollInSchool(
            $student,
            $request->input('school_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Student enrolled in school successfully',
            'data'    => new StudentResource($data),
        ]);
    }

    /**
     * Register subject for student
     */
    public function registerSubject(RegisterSubjectRequest $request, $studentId)
    {
        $data = $this->service->registerSubject($studentId, $request->subject_ids);
        return response()->json([
            'success' => true,
            'message' => 'Subjects registered for student successfully',
            'data' => new StudentResource($data),
        ]);
    }

    /**
     * Report (Graph / relations)
     */
    public function report()
    {
        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'data' =>
            $this->service->report()

        ]);
    }
}
