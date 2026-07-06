# ThreadForge API — Project Status Report

**Date:** 2026-07-06
**Branch:** main
**Laravel:** 13.16.1 | **PHP:** 8.4.20 | **laravel/ai:** 0.8.1

---

## 1. Project Overview

ThreadForge API is a headless REST API built with Laravel that allows tech creators to transform raw technical content (notes, blog articles, GitHub Readmes) into optimized X (Twitter) posts. It uses AI via the `laravel/ai` package with Groq as the default provider.

Key features:
- **Auth** via Laravel Sanctum (Bearer Tokens)
- **Campaign Blueprints** — reusable style rules for content generation
- **Raw Content submission** — async generation via Jobs & Queues
- **Structured Output** — AI returns typed JSON (hook, body points, hashtags, readability score)
- **Post lifecycle management** — draft / posted / archived
- **Ghostwriter Assistant** — conversational AI agent with tools and memory

---

## 2. What Was Implemented

| Feature | Status | Details |
|---------|--------|---------|
| Auth (register/login/logout/me) | Already existed | `AuthController`, Sanctum |
| Campaign Blueprints CRUD | Already existed | `CampaignBlueprintController` |
| Raw Content submission (async) | Already existed | `RawContentController` + `GeneratePostFromRawContentJob` |
| Structured Output (PostGenerator) | Already existed | `PostGenerator` agent with JSON schema |
| Post lifecycle (status update) | Already existed | `GeneratedPostController@updateStatus` |
| Post version history | Already existed | `PostVersionController@index` |
| **Ghostwriter Assistant (US7)** | **NEW** | `GhostwriterController` + `Ghostwriter` agent |
| **Conversation memory (US8)** | **NEW** | `RemembersConversations` + `DatabaseConversationStore` |
| **Tool calling (US9)** | **NEW** | `GetCampaignRulesTool` + `GetPostHistoryTool` |
| **Fix: regenerate with real AI** | **NEW** | `GeneratedPostController@regenerate` now calls `PostGenerator` |
| **Fix: RawContent content cast** | **NEW** | Removed incorrect `'content' => 'array'` cast |

---

## 3. New Files Created

| File | Type | Purpose |
|------|------|---------|
| `app/Ai/Agents/Ghostwriter.php` | Agent | Conversational AI agent with tools and memory |
| `app/Ai/Tools/GetCampaignRulesTool.php` | Tool | Fetches campaign blueprint rules from DB |
| `app/Ai/Tools/GetPostHistoryTool.php` | Tool | Fetches post version history from DB |
| `app/Http/Controllers/Api/GhostwriterController.php` | Controller | Conversations CRUD + message sending |
| `app/Http/Requests/Ghostwriter/StoreConversationRequest.php` | Form Request | Validates conversation creation |
| `app/Http/Requests/Ghostwriter/StoreMessageRequest.php` | Form Request | Validates message sending |
| `app/Http/Resources/ConversationResource.php` | API Resource | JSON serialization for conversations |
| `app/Http/Resources/MessageResource.php` | API Resource | JSON serialization for messages |
| `config/ai.php` | Config | AI provider configuration (16 providers, default: Groq) |
| `database/migrations/2026_07_06_112223_create_agent_conversations_table.php` | Migration | Creates agent_conversations + agent_conversation_messages tables |
| `stubs/agent.stub` | Stub | laravel/ai agent stub |
| `stubs/agent-middleware.stub` | Stub | laravel/ai middleware stub |
| `stubs/structured-agent.stub` | Stub | laravel/ai structured agent stub |
| `stubs/tool.stub` | Stub | laravel/ai tool stub |
| `REPORT.md` | Documentation | This file |

---

## 4. Modified Files

| File | Changes |
|------|---------|
| `app/Models/User.php` | Added `HasConversations` trait from `laravel/ai` |
| `app/Models/RawContent.php` | Removed incorrect `'content' => 'array'` cast |
| `app/Http/Controllers/Api/GeneratedPostController.php` | `regenerate()` now calls `PostGenerator` AI instead of mock data |
| `routes/api.php` | Added 6 Ghostwriter routes + `regenerate` route + import ordering |
| `composer.json` | Added `laravel/ai: ^0.8.1` dependency |
| `composer.lock` | Updated for laravel/ai |
| 29 other files | PSR-12 formatting fixes via Laravel Pint (no logic changes) |

---

## 5. API Routes

### Public

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/health` | Health check |
| POST | `/api/register` | Create account |
| POST | `/api/login` | Get Bearer token |

### Authenticated (auth:sanctum)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/me` | Current user |
| POST | `/api/logout` | Revoke token |
| GET | `/api/blueprints` | List blueprints |
| POST | `/api/blueprints` | Create blueprint |
| GET | `/api/blueprints/{id}` | Show blueprint |
| PUT/PATCH | `/api/blueprints/{id}` | Update blueprint |
| DELETE | `/api/blueprints/{id}` | Delete blueprint |
| GET | `/api/raw-contents` | List raw contents |
| POST | `/api/raw-contents` | Submit raw content (returns 202) |
| GET | `/api/raw-contents/{id}` | Show raw content |
| GET | `/api/generated-posts` | List generated posts |
| GET | `/api/generated-posts/{id}` | Show generated post |
| PATCH | `/api/generated-posts/{id}/status` | Update publication status |
| POST | `/api/generated-posts/{id}/regenerate` | Regenerate with AI |
| GET | `/api/generated-posts/{id}/versions` | List post versions |
| **GET** | **`/api/conversations`** | **List conversations** |
| **POST** | **`/api/conversations`** | **Create conversation** |
| **GET** | **`/api/conversations/{id}`** | **Show conversation** |
| **DELETE** | **`/api/conversations/{id}`** | **Delete conversation** |
| **GET** | **`/api/conversations/{id}/messages`** | **List messages** |
| **POST** | **`/api/conversations/{id}/messages`** | **Send message (AI responds)** |

---

## 6. Architecture: Ghostwriter Assistant

### How It Works

```
Client → POST /api/conversations/{id}/messages
       → GhostwriterController@storeMessage
       → new Ghostwriter(userId: $user->id)
       → $ghostwriter->continue($conversationId, $user)  // loads memory
       → $ghostwriter->prompt("message")                  // sends to AI
       → RememberConversation middleware stores:
           - user message in agent_conversation_messages
           - assistant response in agent_conversation_messages
       → Returns assistant message as JSON
```

### Tools

Both tools implement `Laravel\Ai\Contracts\Tool`:

- **GetCampaignRulesTool** — accepts `campaign_id` (integer), queries `CampaignBlueprint` scoped by `user_id`, returns style rules as JSON
- **GetPostHistoryTool** — accepts `post_id` (integer), queries `GeneratedPost` + `versions`, returns full version history as JSON

### Memory

- Uses `laravel/ai`'s `DatabaseConversationStore` (singleton)
- Conversation history stored in `agent_conversation_messages` table
- `RemembersConversations` trait loads prior messages on `->continue()`
- `#[MaxSteps(10)]` allows up to 10 tool-calling iterations per response

---

## 7. Environment Setup

```env
# Required for AI
GROQ_API_KEY=your_key_here

# Required for async job processing
QUEUE_CONNECTION=database

# Already configured
SANCTUM_STATEFUL_DOMAINS=localhost
```

---

## 8. How to Test (Postman)

### Create conversation
```http
POST /api/conversations
Authorization: Bearer <token>
Content-Type: application/json

{ "title": "Refining my Laravel post" }
```

### Send a message
```http
POST /api/conversations/{UUID}/messages
Authorization: Bearer <token>
Content-Type: application/json

{ "message": "Montre-moi les règles du blueprint 1" }
```

### List post versions
```http
GET /api/generated-posts/1/versions
Authorization: Bearer <token>
```

---

## 9. What Remains To Do

| Priority | Task |
|----------|------|
| High | Feature tests (PHPUnit) for all endpoints |
| High | Factories for CampaignBlueprint, RawContent, GeneratedPost, PostVersion |
| Medium | Database seeders with demo data |
| Medium | Rate limiting on AI-calling endpoints |
| Medium | Pagination metadata wrapper |
| Low | RawContent update/delete endpoints |
| Low | GeneratedPost delete endpoint |
| Low | PostVersion detail view |
| Low | API versioning (/api/v1/) |
| Low | Laravel Policies for ownership |
| Low | Events/Listeners for post generation |
| Low | Notifications for async job completion |

---

## 10. Technical Decisions

1. **No custom conversation models** — `laravel/ai` provides `Conversation` and `ConversationMessage` models. Duplicating them would cause conflicts.
2. **Tools scoped by `$userId`** — Constructor-injected user ID prevents cross-user data access.
3. **Ghostwriter is generic** — Not tied to a specific post in the URL. More flexible than the MCD's `generated_post_id` approach.
4. **Regenerate is synchronous** — Unlike initial submission (async Job), regeneration blocks for immediate feedback.
5. **`user_id` nullable on conversations** — This is a constraint from `laravel/ai`'s migration, not our choice.

---

## 11. Current State

- **29 files changed**, 738 insertions, 162 deletions (from `git diff --stat`)
- **24 API routes** registered
- **9 migrations** all ran successfully
- **Lint:** Laravel Pint passes cleanly
- **PHP syntax:** All files pass `php -l`
