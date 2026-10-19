<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'survey_category_id' => $this->survey_category_id,
            'question_text'      => $this->question_text,
            'sort_order'         => $this->sort_order,
            'options'            => QuestionOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
