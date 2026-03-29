<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;

class Neo4jServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */

    // app/Providers/AppServiceProvider.php



    public function register(): void
    {
        $this->app->singleton(ClientInterface::class, function () {
            return ClientBuilder::create()
                ->withDriver(
                    'bolt',
                    'bolt://' . env('NEO4J_HOST', 'localhost') . ':' . env('NEO4J_PORT', 7687),
                    \Laudis\Neo4j\Authentication\Authenticate::basic(
                        env('NEO4J_USERNAME', 'neo4j'),
                        env('NEO4J_PASSWORD', 'password')
                    )
                )
                ->withDefaultDriver('bolt')
                ->build();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
