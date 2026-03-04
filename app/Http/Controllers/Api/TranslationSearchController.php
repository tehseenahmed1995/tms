<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Services\TranslationService;
use App\DataTransferObjects\SearchCriteriaData;
use Illuminate\Http\JsonResponse;

class TranslationSearchController extends Controller
{
    public function __construct(
        private TranslationService $service,
    ) {}

    public function search(SearchTranslationRequest $request): JsonResponse
    {
        $criteria = SearchCriteriaData::fromRequest($request->validated());
        $results = $this->service->searchTranslations($criteria);

        return response()->json(new \App\Http\Resources\TranslationCollection($results));
    }
}
