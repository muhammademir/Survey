<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'survey_id'   => $this->survey_id,
            'name'        => $this->name,
            'description' => $this->description,
            'sort_order'  => $this->sort_order,
            'questions'   => QuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
