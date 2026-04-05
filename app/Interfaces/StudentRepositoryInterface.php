<?php

namespace App\Interfaces;

use App\Interfaces\CrudRepositoryInterface;

interface StudentRepositoryInterface extends CrudRepositoryInterface
{
    // only student has these extra methods
    public function enrollInSchool($studentId, $schoolId);
    public function registerSubject($studentId, array $subjectIds = []);
    public function report();
    public function existsByEmail(string $email, ?string $exceptId = null): bool;
    public function existsByPhone(string $phone, ?string $exceptId = null): bool;
}
