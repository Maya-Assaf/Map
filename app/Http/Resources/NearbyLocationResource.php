<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NearbyLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource['id'],
            'name' => $this->resource['name'],
            'description' => $this->resource['description'],
            'aspect_id' => $this->resource['aspect_id'],
            'aspect' => $this->resource['aspect'],
            'sub_aspect_id' => $this->resource['sub_aspect_id'],
            'sub_aspect' => $this->resource['sub_aspect'],
            'category_id' => $this->resource['category_id'],
            'category' => $this->resource['category'],
            'user_id' => $this->resource['user_id'],
            'user_layer' => $this->resource['user_layer'],
            'locatable_type' => $this->resource['locatable_type'],
            'locatable' => $this->resource['locatable'],
            'images' => $this->resource['images'],
            'references' => $this->resource['references'],
            'representative_point' => $this->resource['representative_point'],
            'distance_km' => $this->resource['distance_km'],
            'hexagon' => $this->resource['hexagon'],
            'created_at' => $this->resource['created_at'],
            'updated_at' => $this->resource['updated_at'],
        ];
    }
}
