<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(
        private TranslationService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tags = $this->service->getAllTags();
        
        return response()->json([
            'data' => $tags,
        ]);
    }
}
