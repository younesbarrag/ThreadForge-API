<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProcessingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RawContent\StoreRawContentRequest;
use App\Http\Resources\RawContentResource;
use App\Jobs\GeneratePostFromRawContentJob;
use App\Models\RawContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class RawContentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rawContents = $request->user()
            ->rawContents()
            ->with('campaignBlueprint:id,name')
            ->latest()
            ->paginate(10);

        return RawContentResource::collection($rawContents);
    }

    public function store(StoreRawContentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $rawContent = $request->user()->rawContents()->create([
            'campaign_blueprint_id' => $validated['campaign_blueprint_id'],
            'content' => $validated['content'],
            'source_type' => $validated['source_type'] ?? 'text',
            'processing_status' => ProcessingStatus::Pending,
        ]);

        GeneratePostFromRawContentJob::dispatch($rawContent->id);

        $rawContent->load('campaignBlueprint:id,name');

        return (new RawContentResource($rawContent))
            ->additional([
                'message' => 'Raw content accepted for async generation.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function show(Request $request, RawContent $rawContent): RawContentResource
    {
        abort_if($rawContent->user_id !== $request->user()->id, 404);

        $rawContent->load('campaignBlueprint:id,name');

        return new RawContentResource($rawContent);
    }
}
