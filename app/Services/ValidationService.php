<?php

namespace App\Services;

use App\Exceptions\RecordNotFoundException;
use App\Interfaces\SchoolRepositoryInterface;
use App\Interfaces\StudentRepositoryInterface;
use App\Interfaces\SubjectRepositoryInterface;
use Closure;

class ValidationService
{
    public function __construct(
        private StudentRepositoryInterface $studentRepo,
        private SchoolRepositoryInterface  $schoolRepo,
        private SubjectRepositoryInterface $subjectRepo,
    ) {}

    // ─── Exists Checks ────────────────────────────────────────────────
    public function studentExists(): Closure
    {
        return function ($attribute, $value, $fail) {
            try {
                $this->studentRepo->find($value);
            } catch (RecordNotFoundException) {
                $fail('The selected student does not exist.');
            }
        };
    }
    public function schoolExists(): Closure
    {
        return function ($attribute, $value, $fail) {
            try {
                $this->schoolRepo->find($value);
            } catch (RecordNotFoundException) {
                $fail('The selected school does not exist.');
            }
        };
    }

    public function subjectExists(): Closure
    {
        return function ($attribute, $value, $fail) {
            try {
                $this->subjectRepo->find($value);
            } catch (RecordNotFoundException) {
                $fail('The selected subject does not exist.');
            }
        };
    }

    // ─── Unique Checks ─────────────────────────────────────────────────

    public function uniqueStudentEmail(?string $exceptId = null): Closure
    {
        return function ($attribute, $value, $fail) use ($exceptId) {
            if ($this->studentRepo->existsByEmail($value, $exceptId)) {
                $fail('This email is already taken.');
            }
        };
    }

    public function uniqueStudentPhone(?string $exceptId = null): Closure
    {
        return function ($attribute, $value, $fail) use ($exceptId) {
            if ($this->studentRepo->existsByPhone($value, $exceptId)) {
                $fail('This phone number is already taken.');
            }
        };
    }

    public function uniqueSchoolEmail(?string $exceptId = null): Closure
    {
        return function ($attribute, $value, $fail) use ($exceptId) {
            if ($this->schoolRepo->existsByEmail($value, $exceptId)) {
                $fail('This email is already taken.');
            }
        };
    }

    // ─── Array Checks (e.g. subject_ids) ───────────────────────────────

    public function subjectsExist(): Closure
    {
        return function ($attribute, $value, $fail) {
            foreach ((array) $value as $subjectId) {
                try {
                    $this->subjectRepo->find($subjectId);
                } catch (RecordNotFoundException) {
                    $fail("Subject with id '{$subjectId}' does not exist.");
                    return;
                }
            }
        };
    }
}
