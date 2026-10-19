<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SurveyService
{
    /**
     * Ambil daftar survey (admin) dengan filter dan pagination.
     */
    public function list(?string $status, ?string $search): LengthAwarePaginator
    {
        $query = Survey::with('creator')->withCount('responses');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Buat survey baru beserta categories, questions, dan options.
     */
    public function create(array $data, int $createdBy): Survey
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $survey = Survey::create([
                'title'            => $data['title'],
                'description'      => $data['description'] ?? null,
                'status'           => 'draft',
                'publish_start_at' => $data['publish_start_at'] ?? null,
                'publish_end_at'   => $data['publish_end_at'] ?? null,
                'created_by'       => $createdBy,
            ]);

            $this->syncCategories($survey, $data['categories']);

            return $survey->load('categories.questions.options');
        });
    }

    /**
     * Update survey yang masih draft.
     *
     * @throws \InvalidArgumentException
     */
    public function update(Survey $survey, array $data): Survey
    {
        if ($survey->status !== 'draft') {
            throw new \InvalidArgumentException('Survey yang sudah dipublish tidak dapat diedit.');
        }

        return DB::transaction(function () use ($survey, $data) {
            $survey->update([
                'title'            => $data['title'] ?? $survey->title,
                'description'      => $data['description'] ?? $survey->description,
                'publish_start_at' => $data['publish_start_at'] ?? $survey->publish_start_at,
                'publish_end_at'   => $data['publish_end_at'] ?? $survey->publish_end_at,
            ]);

            if (isset($data['categories'])) {
                // Hapus categories lama (cascade ke questions & options)
                $survey->categories()->each(function ($category) {
                    $category->questions()->each(function ($question) {
                        $question->options()->delete();
                    });
                    $category->questions()->delete();
                });
                $survey->categories()->delete();

                $this->syncCategories($survey, $data['categories']);
            }

            return $survey->fresh()->load('categories.questions.options');
        });
    }

    /**
     * Publish survey (draft → published).
     *
     * @throws \InvalidArgumentException
     */
    public function publish(Survey $survey): Survey
    {
        if ($survey->status !== 'draft') {
            throw new \InvalidArgumentException('Survey sudah dipublish sebelumnya.');
        }

        if (! $survey->publish_start_at || ! $survey->publish_end_at) {
            throw new \InvalidArgumentException(
                'Tentukan masa tayang (publish_start_at dan publish_end_at) sebelum publish.'
            );
        }

        $survey->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        return $survey;
    }

    /**
     * Hapus survey.
     *
     * @throws \InvalidArgumentException
     */
    public function delete(Survey $survey): void
    {
        if ($survey->status === 'published' && $survey->responses()->exists()) {
            throw new \InvalidArgumentException(
                'Survey yang sudah memiliki responden tidak dapat dihapus.'
            );
        }

        $survey->delete();
    }

    /**
     * Sinkronisasi categories, questions, dan options ke survey.
     */
    private function syncCategories(Survey $survey, array $categories): void
    {
        foreach ($categories as $catIndex => $catData) {
            $category = $survey->categories()->create([
                'name'        => $catData['name'],
                'description' => $catData['description'] ?? null,
                'sort_order'  => $catIndex,
            ]);

            foreach ($catData['questions'] as $qIndex => $qData) {
                $question = $category->questions()->create([
                    'question_text' => $qData['question_text'],
                    'sort_order'    => $qIndex,
                ]);

                foreach ($qData['options'] as $optData) {
                    $question->options()->create([
                        'label'       => $optData['label'],
                        'option_text' => $optData['option_text'],
                    ]);
                }
            }
        }
    }
}
