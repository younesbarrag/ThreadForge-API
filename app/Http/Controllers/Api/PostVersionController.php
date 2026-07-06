<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostVersionResource;
use App\Models\GeneratedPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostVersionController extends Controller
{
    public function index(Request $request, GeneratedPost $generatedPost): AnonymousResourceCollection
    {
        abort_if($generatedPost->user_id !== $request->user()->id, 404);

        $versions = $generatedPost->versions()->latest('version_number')->paginate(10);

        return PostVersionResource::collection($versions);
    }
}
