<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laudis\Neo4j\Contracts\ClientInterface;

class Neo4jMigrateCommand extends Command
{
    protected $signature = 'neo4j:migrate';
    protected $description = 'Run Neo4j constraints and indexes';

    public function __construct(private ClientInterface $client)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $cypher = file_get_contents(database_path('neo4j/constraints.cypher'));

        // Split by ; and filter empty lines
        $statements = array_filter(
            array_map('trim', explode(';', $cypher)),
            fn($s) => !empty($s) && !str_starts_with($s, '//')
        );

        foreach ($statements as $statement) {
            try {
                $this->client->run($statement);
                $this->info("✅ {$statement}");
            } catch (\Throwable $e) {
                // ✅ Skip if constraint already exists
                if (str_contains($e->getMessage(), 'already exists')) {
                    $this->warn("⚠️ Already exists — skipped");
                    continue;
                }
                $this->error("❌ Failed: {$e->getMessage()}");
            }
        }

        $this->info('Neo4j migration complete.');
    }
}
