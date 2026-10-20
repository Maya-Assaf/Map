<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class NearbyHexagonResponse implements Responsable
{
    public function __construct(
        protected array $gridMetadata,
        protected array $pagination,
        protected array $locations,
        protected string $message = 'Nearby locations retrieved successfully using hexagonal grid',
        protected bool $success = true,
        protected int $statusCode = 200
    ) {}

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'success' => $this->success,
            'message' => $this->message,
            'grid_metadata' => $this->gridMetadata,
            'pagination' => $this->pagination,
            'locations' => $this->locations,
        ], $this->statusCode);
    }
}
