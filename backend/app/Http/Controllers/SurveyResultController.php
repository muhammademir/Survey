<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Services\SurveyResponseService;
use Illuminate\Http\JsonResponse;

class SurveyResultController extends Controller
{
    public function __construct(
        private readonly SurveyResponseService $responseService,
    ) {}

    /**
     * [Admin] Hasil survey - ringkasan
     */
    public function summary(Survey $survey): JsonResponse
    {
        return response()->json(
            $this->responseService->getSummary($survey),
        );
    }

    /**
     * [Admin] Detail responden per survey
     */
    public function respondents(Survey $survey): JsonResponse
    {
        return response()->json(
            $this->responseService->getRespondents($survey),
        );
    }
}
