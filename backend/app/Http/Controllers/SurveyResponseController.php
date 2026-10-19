<?php

namespace App\Http\Controllers;

use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Services\SurveyResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SurveyResponseController extends Controller
{
    public function __construct(
        private readonly SurveyResponseService $responseService,
    ) {}

    /**
     * [User] Daftar survey aktif yang bisa diikuti
     */
    public function availableSurveys(Request $request): JsonResponse
    {
        $surveys = $this->responseService->getAvailableSurveys(
            $request->user()->id,
        );

        return response()->json([
            'surveys' => SurveyResource::collection($surveys),
        ]);
    }

    /**
     * [User] Lihat detail survey untuk diisi
     */
    public function showSurveyForFill(Survey $survey, Request $request): JsonResponse
    {
        try {
            $survey = $this->responseService->getSurveyForFill(
                $survey,
                $request->user()->id,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'survey' => new SurveyResource($survey),
        ]);
    }

    /**
     * [User] Submit jawaban survey
     */
    public function submitResponse(Request $request, Survey $survey): JsonResponse
    {
        if (! $survey->isActive()) {
            abort(404);
        }

        // Validasi format answers
        $survey->load('categories.questions.options');
        $allQuestionIds = $survey->categories
            ->flatMap(fn ($cat) => $cat->questions->pluck('id'))
            ->all();

        $validated = $request->validate([
            'answers'                      => 'required|array|min:1',
            'answers.*.question_id'        => ['required', 'integer', Rule::in($allQuestionIds)],
            'answers.*.question_option_id' => 'required|integer|exists:questions_options,id',
        ]);

        try {
            $response = $this->responseService->submitResponse(
                $survey,
                $request->user()->id,
                $validated['answers'],
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'  => 'Survey berhasil diisi. Terima kasih!',
            'response' => $response,
        ], 201);
    }

    /**
     * [User] Riwayat survey yang pernah diikuti
     */
    public function myHistory(Request $request): JsonResponse
    {
        $responses = $this->responseService->getUserHistory(
            $request->user()->id,
        );

        return response()->json($responses);
    }
}
