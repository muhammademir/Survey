<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\ResponseAnswer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SurveyResponseService
{
    /**
     * Ambil daftar survey aktif yang bisa diikuti user.
     */
    public function getAvailableSurveys(int $userId): Collection
    {
        return Survey::publishedActive()
            ->withCount('responses')
            ->with('categories')
            ->get()
            ->map(function ($survey) use ($userId) {
                $survey->already_responded = $survey->responses()
                    ->where('user_id', $userId)
                    ->exists();
                return $survey;
            });
    }

    /**
     * Ambil detail survey untuk diisi.
     *
     * @throws \InvalidArgumentException
     */
    public function getSurveyForFill(Survey $survey, int $userId): Survey
    {
        if (! $survey->isActive()) {
            throw new NotFoundHttpException();
        }

        if ($this->hasResponded($survey->id, $userId)) {
            throw new \InvalidArgumentException('Anda sudah pernah mengisi survey ini.');
        }

        return $survey->load('categories.questions.options');
    }

    /**
     * Submit jawaban survey dari user.
     *
     * @throws \InvalidArgumentException
     */
    public function submitResponse(Survey $survey, int $userId, array $answers): SurveyResponse
    {
        if (! $survey->isActive()) {
            throw new NotFoundHttpException();
        }

        if ($this->hasResponded($survey->id, $userId)) {
            throw new \InvalidArgumentException('Anda sudah pernah mengisi survey ini.');
        }

        // Validasi semua pertanyaan terjawab
        $survey->load('categories.questions.options');
        $questions = $survey->categories->flatMap(fn ($cat) => $cat->questions);
        $allQuestionIds = $questions->pluck('id')->toArray();

        $answeredIds = array_column($answers, 'question_id');
        $missingIds = array_diff($allQuestionIds, $answeredIds);

        if (count($missingIds) > 0) {
            throw new \InvalidArgumentException('Semua pertanyaan harus dijawab.');
        }

        foreach ($answers as $answer) {
            $question = $questions->firstWhere('id', $answer['question_id']);
            $optionBelongs = $question?->options
                ->contains('id', $answer['question_option_id']);

            if (! $optionBelongs) {
                throw new \InvalidArgumentException('Opsi jawaban tidak valid untuk pertanyaan ini.');
            }
        }

        return DB::transaction(function () use ($survey, $userId, $answers) {
            $surveyResponse = SurveyResponse::create([
                'survey_id' => $survey->id,
                'user_id'   => $userId,
            ]);

            foreach ($answers as $answer) {
                $surveyResponse->answers()->create([
                    'question_id'        => $answer['question_id'],
                    'question_option_id' => $answer['question_option_id'],
                ]);
            }

            return $surveyResponse->load('answers');
        });
    }

    /**
     * Ambil riwayat survey yang pernah diikuti user.
     */
    public function getUserHistory(int $userId): LengthAwarePaginator
    {
        return SurveyResponse::with(['survey', 'answers.question', 'answers.option'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * Ringkasan hasil survey (admin).
     */
    public function getSummary(Survey $survey): array
    {
        $survey->load(['categories.questions.options', 'responses']);
        $totalResponses = $survey->responses->count();

        $categories = $survey->categories->map(function ($category) use ($totalResponses) {
            return [
                'id'          => $category->id,
                'name'        => $category->name,
                'description' => $category->description,
                'questions'   => $category->questions->map(function ($question) use ($totalResponses) {
                    $answerCounts = ResponseAnswer::where('question_id', $question->id)
                        ->select('question_option_id', DB::raw('COUNT(*) as count'))
                        ->groupBy('question_option_id')
                        ->pluck('count', 'question_option_id')
                        ->toArray();

                    return [
                        'id'            => $question->id,
                        'question_text' => $question->question_text,
                        'options'       => $question->options->map(function ($option) use ($answerCounts, $totalResponses) {
                            $count = $answerCounts[$option->id] ?? 0;
                            return [
                                'id'          => $option->id,
                                'label'       => $option->label,
                                'option_text' => $option->option_text,
                                'count'       => $count,
                                'percentage'  => $totalResponses > 0
                                    ? round(($count / $totalResponses) * 100, 1)
                                    : 0,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return [
            'survey' => [
                'id'               => $survey->id,
                'title'            => $survey->title,
                'description'      => $survey->description,
                'status'           => $survey->status,
                'total_responses'  => $totalResponses,
                'publish_start_at' => $survey->publish_start_at,
                'publish_end_at'   => $survey->publish_end_at,
            ],
            'categories' => $categories,
        ];
    }

    /**
     * Ambil daftar responden per survey (admin).
     */
    public function getRespondents(Survey $survey): LengthAwarePaginator
    {
        return $survey->responses()
            ->with(['user:id,name,email', 'answers.question', 'answers.option'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * Cek apakah user sudah pernah mengisi survey.
     */
    private function hasResponded(int $surveyId, int $userId): bool
    {
        return SurveyResponse::where('survey_id', $surveyId)
            ->where('user_id', $userId)
            ->exists();
    }
}
