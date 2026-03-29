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
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // register Neo4j client (always, even if using MySQL)
        $this->app->singleton(ClientInterface::class, function () {
            return ClientBuilder::create()
                ->withDriver(
                    'bolt',
                    'bolt://' . env('NEO4J_HOST', 'localhost') . ':' . env('NEO4J_PORT', 7687),
                    Authenticate::basic(
                        env('NEO4J_USERNAME', 'neo4j'),
                        env('NEO4J_PASSWORD', 'password')
                    )
                )
                ->withDefaultDriver('bolt')
                ->build();
        });

        // true  → Neo4j repos
        // false → Eloquent repos
        $useNeo4j = env('DB_DRIVER', 'eloquent') === 'neo4j';

        $this->app->bind(
            SchoolRepositoryInterface::class,
            $useNeo4j ? SchoolNeo4jRepository::class : SchoolRepository::class
        );

        $this->app->bind(
            SubjectRepositoryInterface::class,
            $useNeo4j ? SubjectNeo4jRepository::class : SubjectRepository::class
        );

        $this->app->bind(
            StudentRepositoryInterface::class,
            $useNeo4j ? StudentNeo4jRepository::class : StudentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
