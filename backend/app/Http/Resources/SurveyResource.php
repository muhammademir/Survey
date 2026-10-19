<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'status'           => $this->status,
            'publish_start_at' => $this->publish_start_at,
            'publish_end_at'   => $this->publish_end_at,
            'published_at'     => $this->published_at,
            'created_by'       => $this->created_by,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
            'creator'          => new UserResource($this->whenLoaded('creator')),
            'categories'       => SurveyCategoryResource::collection($this->whenLoaded('categories')),
            'responses_count'  => $this->when(isset($this->responses_count), $this->responses_count),
            'already_responded' => $this->when(isset($this->already_responded), $this->already_responded),
        ];
    }
}
