<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TranslationExportService;
use App\DataTransferObjects\ExportOptionsData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TranslationExportController extends Controller
{
    public function __construct(
        private TranslationExportService $service,
    ) {}

    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|size:2',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
            'nested' => 'sometimes|boolean',
        ]);

        $options = ExportOptionsData::fromRequest($request->all());
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
