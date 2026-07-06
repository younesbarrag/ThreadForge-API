<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\Ghostwriter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ghostwriter\StoreConversationRequest;
use App\Http\Requests\Ghostwriter\StoreMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;

class GhostwriterController extends Controller
{
    public function indexConversations(Request $request): AnonymousResourceCollection
    {
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->withCount('messages')
            ->latest()
            ->paginate(10);

        return ConversationResource::collection($conversations);
    }

    public function storeConversation(
        StoreConversationRequest $request,
    ): JsonResponse {
        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'title' => $request->validated('title', 'New conversation'),
        ]);

        return (new ConversationResource($conversation))
            ->additional([
                'message' => 'Conversation created successfully. Send a message to start chatting.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function showConversation(
        Request $request,
        string $conversation,
    ): ConversationResource {
        $conversationModel = Conversation::where('id', $conversation)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $conversationModel->loadCount('messages');

        return new ConversationResource($conversationModel);
    }

    public function destroyConversation(
        Request $request,
        string $conversation,
    ): JsonResponse {
        $conversationModel = Conversation::where('id', $conversation)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        ConversationMessage::where('conversation_id', $conversationModel->id)->delete();
        $conversationModel->delete();

        return response()->json([
            'message' => 'Conversation deleted successfully.',
        ]);
    }

    public function indexMessages(
        Request $request,
        string $conversation,
    ): AnonymousResourceCollection {
        Conversation::where('id', $conversation)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $messages = ConversationMessage::where('conversation_id', $conversation)
            ->orderBy('created_at')
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    public function storeMessage(
        StoreMessageRequest $request,
        string $conversation,
    ): JsonResponse {
        $user = $request->user();

        $conversationModel = Conversation::where('id', $conversation)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $ghostwriter = new Ghostwriter(userId: $user->id);
        $ghostwriter->continue($conversationModel->id, $user);

        $response = $ghostwriter->prompt($request->validated('message'));

        $assistantMessage = ConversationMessage::where('conversation_id', $conversationModel->id)
            ->where('role', 'assistant')
            ->latest()
            ->first();

        $conversationModel->touch();

        return (new MessageResource($assistantMessage))
            ->additional([
                'conversation_id' => $conversationModel->id,
                'usage' => [
                    'text_tokens' => $response->usage()->textTokens(),
                    'input_tokens' => $response->usage()->inputTokens(),
                    'output_tokens' => $response->usage()->outputTokens(),
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }
}
