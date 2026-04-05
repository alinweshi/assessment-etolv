<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    protected function data(): array
    {
        return is_array($this->resource)
            ? $this->resource
            : $this->resource->toArray();
    }
}
