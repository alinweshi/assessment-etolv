<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Laudis\Neo4j\Types\DateTime as Neo4jDateTime;


abstract class BaseResource extends JsonResource
{
    protected function data(): array
    {
        return is_array($this->resource)
            ? $this->resource
            : $this->resource->toArray();
    }
    protected function formatDate(mixed $value): ?string
    {
        if (!$value) return null;

        return match (true) {
            $value instanceof Carbon =>
            $value->timezone(config('app.timezone'))->toDateTimeString(),

            $value instanceof \Laudis\Neo4j\Types\DateTime =>
            Carbon::createFromTimestampUTC($value->getSeconds())
                ->setTimezone(config('app.timezone'))
                ->toDateTimeString(),

            is_string($value) =>
            Carbon::parse($value)->timezone(config('app.timezone'))->toDateTimeString(),

            default => null,
        };
    }
}
