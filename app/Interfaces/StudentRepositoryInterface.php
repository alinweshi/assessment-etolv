<?php

namespace App\Interfaces;

interface StudentRepositoryInterface extends CrudRepositoryInterface
{
    // only student has these extra methods
    public function enrollInSchool($studentId, $schoolId);
    public function registerSubject($studentId, $subjectId);
    public function report();
}
