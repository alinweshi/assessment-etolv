<?php

namespace App\Providers;

use App\Interfaces\SchoolRepositoryInterface;
use App\Interfaces\StudentRepositoryInterface;
use App\Interfaces\SubjectRepositoryInterface;

// Eloquent
use App\Repositories\Eloquent\SchoolRepository;
use App\Repositories\Eloquent\StudentRepository;
use App\Repositories\Eloquent\SubjectRepository;

// Neo4j
use App\Repositories\Neo4j\SchoolNeo4jRepository;
use App\Repositories\Neo4j\StudentNeo4jRepository;
use App\Repositories\Neo4j\SubjectNeo4jRepository;

use App\Services\ValidationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private array $repositories = [
        SchoolRepositoryInterface::class => [
            'eloquent' => SchoolRepository::class,
            'neo4j'    => SchoolNeo4jRepository::class,
        ],
        StudentRepositoryInterface::class => [
            'eloquent' => StudentRepository::class,
            'neo4j'    => StudentNeo4jRepository::class,
        ],
        SubjectRepositoryInterface::class => [
            'eloquent' => SubjectRepository::class,
            'neo4j'    => SubjectNeo4jRepository::class,
        ],
    ];

    public function register(): void
    {
        $driver = config('repository.driver', 'eloquent');

        foreach ($this->repositories as $interface => $map) {
            $concrete = $map[$driver] ?? $map['eloquent'];

            $this->app->bind(
                $interface,
                fn() => $this->app->make($concrete)
            );
        }

        $this->app->singleton(ValidationService::class);
    }

    public function boot(): void {}
}
