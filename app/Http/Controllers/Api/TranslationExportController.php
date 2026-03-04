<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportTranslationRequest;
use App\Services\TranslationExportService;
use App\DataTransferObjects\ExportOptionsData;
use Illuminate\Http\JsonResponse;

class TranslationExportController extends Controller
{
    public function __construct(
        private TranslationExportService $service,
    ) {}

    public function export(ExportTranslationRequest $request): JsonResponse
    {
        $options = ExportOptionsData::fromRequest($request->validated());
        $etag = $this->service->getExportETag($options);

        // Handle ETag for 304 Not Modified
        if ($request->header('If-None-Match') === $etag) {
            return response()->json(null, 304);
        }

        $data = $this->service->exportToJson($options);

        return response()->json($data)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Last-Modified', now()->toRfc7231String());
    }
}
