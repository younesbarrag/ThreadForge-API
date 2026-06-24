<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignBlueprint\StoreCampaignBlueprintRequest;
use App\Http\Requests\CampaignBlueprint\UpdateCampaignBlueprintRequest;
use App\Http\Resources\CampaignBlueprintResource;
use App\Models\CampaignBlueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CampaignBlueprintController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $blueprints = $request->user()
            ->campaignBlueprints()
            ->withCount('generatedPosts')
            ->latest()
            ->paginate(10);

        return CampaignBlueprintResource::collection($blueprints);
    }

    public function store(StoreCampaignBlueprintRequest $request): JsonResponse
    {
        $blueprint = $request->user()
            ->campaignBlueprints()
            ->create($request->validated());

        return (new CampaignBlueprintResource($blueprint))
            ->additional([
                'message' => 'Campaign blueprint created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, CampaignBlueprint $blueprint): CampaignBlueprintResource
    {
        $this->ensureUserOwnsBlueprint($request, $blueprint);

        $blueprint->loadCount('generatedPosts');

        return new CampaignBlueprintResource($blueprint);
    }

   public function update(
    UpdateCampaignBlueprintRequest $request,
    CampaignBlueprint $blueprint
): CampaignBlueprintResource {
    $this->ensureUserOwnsBlueprint($request, $blueprint);

    $blueprint->update($request->validated());

    $blueprint->loadCount('generatedPosts');

    return new CampaignBlueprintResource($blueprint);
}
    public function destroy(Request $request, CampaignBlueprint $blueprint): JsonResponse
    {
        $this->ensureUserOwnsBlueprint($request, $blueprint);

        $blueprint->delete();

        return response()->json([
            'message' => 'Campaign blueprint deleted successfully.',
        ]);
    }

    private function ensureUserOwnsBlueprint(Request $request, CampaignBlueprint $blueprint): void
    {
        abort_if($blueprint->user_id !== $request->user()->id, 404);
    }
}