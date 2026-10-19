<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Services\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function __construct(
        private readonly SurveyService $surveyService,
    ) {}

    /**
     * [Admin] Daftar semua survey
     */
    public function index(Request $request): JsonResponse
    {
        $surveys = $this->surveyService->list(
            $request->input('status'),
            $request->input('search'),
        );

        return response()->json($surveys);
    }

    /**
     * [Admin] Buat survey baru (status = draft)
     */
    public function store(StoreSurveyRequest $request): JsonResponse
    {
        $survey = $this->surveyService->create(
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'message' => 'Survey berhasil dibuat',
            'survey'  => new SurveyResource($survey),
        ], 201);
    }

    /**
     * [Admin] Lihat detail survey
     */
    public function show(Survey $survey): JsonResponse
    {
        $survey->load(['categories.questions.options', 'creator']);
        $survey->loadCount('responses');

        return response()->json([
            'survey' => new SurveyResource($survey),
        ]);
    }

    /**
     * [Admin] Update survey (hanya saat masih draft)
     */
    public function update(UpdateSurveyRequest $request, Survey $survey): JsonResponse
    {
        try {
            $survey = $this->surveyService->update($survey, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Survey berhasil diperbarui',
            'survey'  => new SurveyResource($survey),
        ]);
    }

    /**
     * [Admin] Publish survey (draft → published)
     */
    public function publish(Survey $survey): JsonResponse
    {
        try {
            $survey = $this->surveyService->publish($survey);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Survey berhasil dipublish',
            'survey'  => new SurveyResource($survey),
        ]);
    }

    /**
     * [Admin] Hapus survey
     */
    public function destroy(Survey $survey): JsonResponse
    {
        try {
            $this->surveyService->delete($survey);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Survey berhasil dihapus',
        ]);
    }
}
