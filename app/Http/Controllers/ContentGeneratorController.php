<?php

namespace App\Http\Controllers;

use App\Services\ContentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentGeneratorController extends Controller
{
    protected $contentGenerator;

    public function __construct(ContentGeneratorService $contentGenerator)
    {
        $this->contentGenerator = $contentGenerator;
        $this->middleware(['auth', 'oauth.valid']);
    }

    /**
     * Generate title suggestions based on topic.
     */
    public function generateTitles(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'count' => 'sometimes|integer|min:1|max:10'
        ]);

        $result = $this->contentGenerator->generateTitle(
            $request->topic,
            $request->count ?? 3
        );

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error']
            ], 500);
        }

        return response()->json([
            'titles' => $result['titles']
        ]);
    }

    /**
     * Generate content based on title and keywords.
     */
    public function generateContent(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'keywords' => 'sometimes|array',
            'keywords.*' => 'string|max:50'
        ]);

        $result = $this->contentGenerator->generateContent(
            $request->title,
            $request->keywords ?? []
        );

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error']
            ], 500);
        }

        return response()->json([
            'content' => $result['content']
        ]);
    }

    /**
     * Generate both title and content in one request.
     */
    public function generatePost(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'keywords' => 'sometimes|array',
            'keywords.*' => 'string|max:50'
        ]);

        // First generate a title
        $titleResult = $this->contentGenerator->generateTitle($request->topic, 1);
        if (!$titleResult['success']) {
            return response()->json([
                'error' => 'Failed to generate title'
            ], 500);
        }

        // Then generate content using the first title
        $contentResult = $this->contentGenerator->generateContent(
            $titleResult['titles'][0],
            $request->keywords ?? []
        );

        if (!$contentResult['success']) {
            return response()->json([
                'error' => 'Failed to generate content'
            ], 500);
        }

        return response()->json([
            'title' => $titleResult['titles'][0],
            'content' => $contentResult['content']
        ]);
    }
}
