<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Http\Resources\TranslationCollection;
use App\Services\TranslationService;
use App\DataTransferObjects\TranslationData;
use App\Models\Translation;
use Illuminate\Http\JsonResponse;

class TranslationController extends Controller
{
    public function __construct(
        private TranslationService $service,
    ) {}

    public function index(): JsonResponse
    {
        $translations = Translation::with('tags')->paginate(15);
        return response()->json(new TranslationCollection($translations));
    }

    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $data = TranslationData::fromRequest($request->validated());
        $translation = $this->service->createTranslation($data);

        return response()->json(
            new TranslationResource($translation),
            201
        );
    }

    public function show(Translation $translation): JsonResponse
    {
        return response()->json(new TranslationResource($translation->load('tags')));
    }

    public function update(UpdateTranslationRequest $request, Translation $translation): JsonResponse
    {
        $validated = $request->validated();
        
        // Merge existing translation data with update data
        $mergedData = array_merge([
            'key' => $translation->key,
            'locale' => $translation->locale,
            'content' => $translation->content,
            'tags' => $translation->tags->pluck('name')->toArray(),
        ], $validated);
        
        $data = TranslationData::fromRequest($mergedData);
        $updated = $this->service->updateTranslation($translation, $data);

        return response()->json(new TranslationResource($updated));
    }

    public function destroy(Translation $translation): JsonResponse
    {
        $this->service->deleteTranslation($translation);
        return response()->json(null, 204);
    }
}
