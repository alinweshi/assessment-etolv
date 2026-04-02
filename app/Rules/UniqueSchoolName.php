<?php

namespace App\Rules;

use App\Interfaces\SchoolRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniqueSchoolName implements ValidationRule
{

    protected $repo;

    public function __construct()
    {
        $this->repo = app(SchoolRepositoryInterface::class);
    }
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $school = $this->repo->findByName($value);

        if (!empty($school)) {
            $fail('The school name already exists.');
        }
    }


    /*************  ✨ Windsurf Command ⭐  *************/
    /**
     * Get the validation message.
     *
     * @return string
     */
    /*******  33dcde31-3c33-494f-b22a-83ea707d103c  *******/
    public function message()
    {
        return 'The school name already exists.';
    }
}
