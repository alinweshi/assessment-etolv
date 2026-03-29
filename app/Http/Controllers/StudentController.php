<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterSubjectRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Resources\ReportResource;
use App\Http\Resources\StudentResource;
use App\Services\StudentService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        protected StudentService $service
    ) {}

    /**
     * Get all students
     */
    public function index()
    {
        return StudentResource::collection(
            $this->service->all()
        );
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
            $request->all()
        );

        return (new StudentResource($student))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update student
     */
    public function update(Request $request, $id)
    {
        return new StudentResource(
            $this->service->update($id, $request->all())
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
    public function enroll($studentId, $schoolId)
    {
        return new StudentResource(
            $this->service->enrollInSchool($studentId, $schoolId)
        );
    }

    /**
     * Register subject for student
     */
    public function registerSubject(RegisterSubjectRequest $request, $studentId)
    {
        return new StudentResource(
            $this->service->registerSubject($studentId, $request->subject_ids)
        );
    }

    /**
     * Report (Graph / relations)
     */
    public function report()
    {
        return ReportResource::collection(
            $this->service->report()
        );
    }
}
