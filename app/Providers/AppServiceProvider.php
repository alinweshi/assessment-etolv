<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\Contracts\ClientInterface;

use App\Interfaces\SchoolRepositoryInterface;
use App\Interfaces\SubjectRepositoryInterface;
use App\Interfaces\StudentRepositoryInterface;

use App\Repositories\Eloquent\SchoolRepository;
use App\Repositories\Eloquent\SubjectRepository;
use App\Repositories\Eloquent\StudentRepository;

use App\Repositories\Neo4j\SchoolNeo4jRepository;
use App\Repositories\Neo4j\SubjectNeo4jRepository;
use App\Repositories\Neo4j\StudentNeo4jRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /**
         * ✅ 1. Register Neo4j Client (Singleton)
         */
        $this->app->singleton(ClientInterface::class, function () {
            return ClientBuilder::create()
                ->withDriver(
                    'bolt',
                    env('NEO4J_URI', 'bolt://127.0.0.1:7687'),
                    Authenticate::basic(
                        env('NEO4J_USERNAME', 'neo4j'),
                        env('NEO4J_PASSWORD', 'password')
                    )
                )
                ->withDefaultDriver('bolt')
                ->build();
        });

        /**
         * ✅ 2. Get Driver from config (مش env مباشر)
         */
        $driver = config('repository.driver');

        /**
         * ✅ 3. Bind Repositories Dynamically
         */
        $this->bindRepositories($driver);
    }

    /**
     * 🔥 فصل الـ binding في method (أنضف + scalable)
     */
    private function bindRepositories(string $driver): void
    {
        /**
         * 🎯 School
         */
        $this->app->bind(SchoolRepositoryInterface::class, function ($app) use ($driver) {
            return match ($driver) {
                'neo4j' => $app->make(SchoolNeo4jRepository::class),
                default => $app->make(SchoolRepository::class),
            };
        });

        /**
         * 🎯 Subject
         */
        $this->app->bind(SubjectRepositoryInterface::class, function ($app) use ($driver) {
            return match ($driver) {
                'neo4j' => $app->make(SubjectNeo4jRepository::class),
                default => $app->make(SubjectRepository::class),
            };
        });

        /**
         * 🎯 Student
         */
        $this->app->bind(StudentRepositoryInterface::class, function ($app) use ($driver) {
            return match ($driver) {
                'neo4j' => $app->make(StudentNeo4jRepository::class),
                default => $app->make(StudentRepository::class),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
