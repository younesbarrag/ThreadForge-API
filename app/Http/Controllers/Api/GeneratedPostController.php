<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneratedPostResource;
use App\Models\GeneratedPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\GeneratedPost\UpdateGeneratedPostStatusRequest;

class GeneratedPostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $generatedPosts = $request->user()
            ->generatedPosts()
            ->with([
                'campaignBlueprint:id,name,tone',
                'rawContent:id,content,source_type,processing_status',
            ])
            ->latest()
            ->paginate(10);

        return GeneratedPostResource::collection($generatedPosts);
    }

    public function show(Request $request, GeneratedPost $generatedPost): GeneratedPostResource
    {
        abort_if($generatedPost->user_id !== $request->user()->id, 404);

        $generatedPost->load([
            'campaignBlueprint:id,name,tone',
            'rawContent:id,content,source_type,processing_status',
        ]);

        return new GeneratedPostResource($generatedPost);
    }

    public function updateStatus( UpdateGeneratedPostStatusRequest $request, GeneratedPost $generatedPost


    
): GeneratedPostResource {
    abort_if($generatedPost->user_id !== $request->user()->id, 404);

    $validated = $request->validated();

    $generatedPost->update([
        'publication_status' => $validated['publication_status'],
    ]);

    $generatedPost->load([
        'campaignBlueprint:id,name,tone',
        'rawContent:id,content,source_type,processing_status',
    ]);

    return new GeneratedPostResource($generatedPost);
}
}