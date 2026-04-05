<?php

namespace App\Interfaces;

use App\Interfaces\CrudRepositoryInterface;

interface SchoolRepositoryInterface extends CrudRepositoryInterface
{
    public function existsByEmail(string $email, ?string $exceptId = null): bool;
    public function existsByPhone(string $phone, ?string $exceptId = null): bool;
}
