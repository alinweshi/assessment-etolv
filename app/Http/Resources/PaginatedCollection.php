<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PaginatedCollection extends ResourceCollection
{
    protected array $meta;

    public function __construct($resource, array $meta)
    {
        parent::__construct($resource);
        $this->meta = $meta;
    }

    public function with($request): array
    {
        return ['meta' => $this->meta];
    }
}
