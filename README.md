# ThreadForge API

**A headless Laravel REST API that transforms raw technical content into optimized social media posts using AI.**

---

## Table of Contents

- [Purpose](#purpose)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [Application Architecture](#application-architecture)
- [Main Domain Entities](#main-domain-entities)
- [AI Integration](#ai-integration)
- [Queue and Background Job Processing](#queue-and-background-job-processing)
- [Authentication and Authorization](#authentication-and-authorization)
- [API Overview](#api-overview)
- [Complete API Endpoint Table](#complete-api-endpoint-table)
- [Installation Requirements](#installation-requirements)
- [Local Installation Steps](#local-installation-steps)
- [Environment Configuration](#environment-configuration)
- [Database Setup](#database-setup)
- [Queue Setup](#queue-setup)
- [Running the Application Locally](#running-the-application-locally)
- [Running Tests](#running-tests)
- [Code Quality Tools](#code-quality-tools)
- [CI/CD Pipeline](#cicd-pipeline)
- [Production Deployment Overview](#production-deployment-overview)
- [Supervisor Worker Configuration](#supervisor-worker-configuration)
- [Example API Workflow](#example-api-workflow)
- [Example JSON Requests and Responses](#example-json-requests-and-responses)
- [Project Structure](#project-structure)
- [Security Notes](#security-notes)
- [Troubleshooting](#troubleshooting)
- [Future Improvements](#future-improvements)
- [Author](#author)
- [License](#license)

---

## Purpose

ThreadForge API is a headless REST API built with Laravel that enables developers and content creators to automatically transform raw technical content -- such as development notes, README files, markdown documents, or blog posts -- into polished, optimized social media posts for X (Twitter). The API uses AI agents to apply reusable style rules defined through Campaign Blueprints, ensuring consistent brand voice and content quality.

---

## Key Features

- **Campaign Blueprints** -- Define reusable style rules (tone, audience, hashtag limits, character limits, additional rules) that govern AI-generated content.
- **Raw Content Submission** -- Submit text, markdown, or README content for asynchronous processing.
- **Asynchronous AI Post Generation** -- Background jobs call AI providers to generate structured posts without blocking HTTP responses.
- **Post Versioning** -- Every generation and regeneration creates a new version, preserving a full history of content iterations.
- **Publication Status Management** -- Track posts through draft, posted, and archived states.
- **AI Ghostwriter Assistant** -- Conversational AI agent with memory, capable of accessing campaign rules and post history through tool calls.
- **Token-based Authentication** -- Secure API access using Laravel Sanctum personal access tokens.
- **Multi-tenant Data Isolation** -- Each user only sees and manages their own resources.
- **Health Check Endpoint** -- Verify API availability with a simple GET request.
- **Automated Testing** -- Comprehensive PHPUnit feature tests covering authentication, authorization, validation, and the full post lifecycle.
- **CI/CD Pipeline** -- GitHub Actions workflow for automated testing on every push and pull request.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.3+ |
| Framework | Laravel 13.8 |
| AI SDK | laravel/ai 0.8.1 |
| Authentication | Laravel Sanctum 4.0 |
| Database | MySQL 8+ (SQLite for testing) |
| Queue Driver | Database (default) |
| Testing | PHPUnit 12.5 |
| Code Style | Laravel Pint |
| CI/CD | GitHub Actions |
| Build Tool | Vite 8 |
| CSS | Tailwind CSS 4 |

---

## Application Architecture

```
Client (mobile, SPA, CLI)
  |
  v
Laravel REST API (routes/api.php)
  |
  v
Authentication (Laravel Sanctum)
  |
  v
Form Request Validation
  |
  v
Controllers + Eloquent Models
  |
  +---> Database (MySQL)
  |
  +---> Queued Job (GeneratePostFromRawContentJob)
            |
            v
        AI Agent (PostGenerator / Ghostwriter)
            |
            v
        AI Provider (Groq, OpenAI, Anthropic, etc.)
            |
            v
        Structured Output stored in database
```

When raw content is submitted, the API immediately returns HTTP `202 Accepted`. A background job (`GeneratePostFromRawContentJob`) is dispatched to the queue, which calls the AI agent, processes the structured response, and stores the generated post and its first version in the database.

---

## Main Domain Entities

| Entity | Description |
|---|---|
| **User** | The content creator who owns all resources. Authenticates via Sanctum tokens. |
| **CampaignBlueprint** | A reusable style configuration defining tone, target audience, hashtag limits, character limits, and additional rules for AI generation. |
| **RawContent** | The user-submitted source material (text, markdown, README). Tracks processing status (pending, processing, completed, failed). |
| **GeneratedPost** | The AI-generated post output containing a hook, body points, hashtags, readability score, and tone compliance justification. Tracks publication status (draft, posted, archived). |
| **PostVersion** | A snapshot of a generated post at a point in time, preserving the full version history across generations and regenerations. |

### Entity Relationships

```
User (1) ---> (N) CampaignBlueprint
User (1) ---> (N) RawContent
User (1) ---> (N) GeneratedPost

CampaignBlueprint (1) ---> (N) RawContent
CampaignBlueprint (1) ---> (N) GeneratedPost

RawContent (1) ---> (0..1) GeneratedPost

GeneratedPost (1) ---> (N) PostVersion
```

---

## AI Integration

ThreadForge uses the `laravel/ai` SDK to interact with AI providers through structured agents.

### PostGenerator Agent

- **Location:** `app/Ai/Agents/PostGenerator.php`
- **Role:** Transforms raw technical content into structured X (Twitter) posts.
- **Output Schema:** Returns a strict JSON structure with:
  - `hook_proposal` (string, max 280 chars)
  - `body_points` (array of strings)
  - `technical_readability_score` (integer, 0-100)
  - `suggested_hashtags` (array of strings)
  - `tone_compliance_justification` (string)
- **Prompt injection protection:** Raw content is treated as untrusted source material. The agent is instructed to extract technical information only, not follow embedded instructions.

### Ghostwriter Agent

- **Location:** `app/Ai/Agents/Ghostwriter.php`
- **Role:** Conversational assistant for refining, iterating, and improving generated posts.
- **Features:** Maintains conversation memory, accesses campaign rules and post history through tool calls.
- **Max Steps:** 10 per interaction.

### AI Tools

| Tool | Location | Purpose |
|---|---|---|
| `GetCampaignRulesTool` | `app/Ai/Tools/GetCampaignRulesTool.php` | Retrieves style rules and constraints of a Campaign Blueprint by ID. |
| `GetPostHistoryTool` | `app/Ai/Tools/GetPostHistoryTool.php` | Retrieves the full version history of a Generated Post by ID. |

### Supported AI Providers

The default provider is **Groq**. The `config/ai.php` configuration includes support for:

- Anthropic
- Azure OpenAI
- AWS Bedrock
- Cohere
- DeepSeek
- ElevenLabs
- Gemini
- Groq (default)
- Jina
- Mistral
- Ollama
- OpenAI
- OpenRouter
- VoyageAI
- xAI

---

## Queue and Background Job Processing

### GeneratePostFromRawContentJob

- **Location:** `app/Jobs/GeneratePostFromRawContentJob.php`
- **Implements:** `ShouldQueue`
- **Timeout:** 120 seconds
- **Max Attempts:** 3
- **Backoff:** 10s, 30s, 60s
- **Fail on Timeout:** Yes

### Job Flow

1. Marks raw content status as `processing`.
2. Extracts blueprint rules (excluding technical metadata).
3. Calls the `PostGenerator` AI agent with the raw content and blueprint rules.
4. Parses the structured AI response.
5. Creates or updates a `GeneratedPost` record.
6. Creates a new `PostVersion` snapshot (version 1, 2, 3, ...).
7. Marks raw content status as `completed`.
8. On failure, marks raw content status as `failed` and stores the error message.

### Running the Queue Worker

```bash
php artisan queue:work --tries=1 --timeout=0
```

For development with all services:

```bash
composer dev
```

This runs the server, queue worker, Pail log viewer, and Vite dev server concurrently.

---

## Authentication and Authorization

ThreadForge uses **Laravel Sanctum** for token-based API authentication.

### How It Works

1. Register or log in to receive a personal access token.
2. Include the token in the `Authorization` header as `Bearer <token>` for all authenticated requests.
3. Each user can only access their own resources. Ownership checks are enforced at the controller level.

### Token Details

- **Token Name:** `threadforge-api-token`
- **Expiration:** Not set (tokens do not expire by default)
- **Guard:** Web

### Ownership Enforcement

Every protected endpoint verifies that the authenticated user owns the requested resource. Unauthorized access returns HTTP `404` (not `403`) to prevent information leakage about resource existence.

---

## API Overview

All endpoints are prefixed with `/api`. The API follows RESTful conventions and returns JSON responses.

### Public Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/health` | Health check |
| `POST` | `/api/register` | Create account |
| `POST` | `/api/login` | Authenticate |

### Authenticated Endpoints

All endpoints below require a valid Sanctum Bearer token.

---

## Complete API Endpoint Table

### Authentication

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/api/health` | No | Health check -- returns status and message |
| `POST` | `/api/register` | No | Register a new user account |
| `POST` | `/api/login` | No | Log in and receive a Bearer token |
| `GET` | `/api/me` | Yes | Get the authenticated user profile |
| `POST` | `/api/logout` | Yes | Revoke the current access token |

### Campaign Blueprints

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/api/blueprints` | Yes | List all blueprints (paginated, 10 per page) |
| `POST` | `/api/blueprints` | Yes | Create a new campaign blueprint |
| `GET` | `/api/blueprints/{blueprint}` | Yes | Get a specific blueprint |
| `PUT` | `/api/blueprints/{blueprint}` | Yes | Update a blueprint |
| `DELETE` | `/api/blueprints/{blueprint}` | Yes | Delete a blueprint |

### Raw Contents

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/api/raw-contents` | Yes | List all raw contents (paginated) |
| `POST` | `/api/raw-contents` | Yes | Submit raw content for async post generation |
| `GET` | `/api/raw-contents/{rawContent}` | Yes | Get a specific raw content |
| `POST` | `/api/content/repurpose` | Yes | Alias for submitting raw content |

### Generated Posts

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/api/generated-posts` | Yes | List all generated posts (paginated) |
| `GET` | `/api/generated-posts/{generatedPost}` | Yes | Get a specific generated post |
| `PATCH` | `/api/generated-posts/{generatedPost}/status` | Yes | Update publication status (draft/posted/archived) |
| `POST` | `/api/generated-posts/{generatedPost}/regenerate` | Yes | Regenerate the post with AI (creates new version) |

### Post Versions

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/api/generated-posts/{generatedPost}/versions` | Yes | List all versions of a generated post (paginated) |

### Ghostwriter Conversations

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/api/conversations` | Yes | List all conversations (paginated) |
| `POST` | `/api/conversations` | Yes | Create a new conversation |
| `GET` | `/api/conversations/{conversation}` | Yes | Get a specific conversation |
| `DELETE` | `/api/conversations/{conversation}` | Yes | Delete a conversation and its messages |
| `GET` | `/api/conversations/{conversation}/messages` | Yes | List messages in a conversation (paginated, 50 per page) |
| `POST` | `/api/conversations/{conversation}/messages` | Yes | Send a message and receive AI response |

---

## Installation Requirements

- PHP 8.3 or higher
- Composer
- MySQL 8.0+ (or SQLite for development/testing)
- Node.js 18+ and npm (for frontend assets)
- A Groq API key (or another supported AI provider key)

---

## Local Installation Steps

```bash
git clone https://github.com/younesbarrag/threadforge-api.git
cd threadforge-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
```

---

## Environment Configuration

Copy `.env.example` to `.env` and configure the following variables:

### Application

```env
APP_NAME=ThreadForge
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

### Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=threadforge
DB_USERNAME=threadforge_user
DB_PASSWORD=your_secure_password
```

### Queue

```env
QUEUE_CONNECTION=database
```

### AI Provider

```env
AI_PROVIDER=groq
GROQ_API_KEY=your_groq_api_key
```

### Session and Cache

```env
SESSION_DRIVER=database
CACHE_STORE=database
```

---

## Database Setup

Run the migrations to create all required tables:

```bash
php artisan migrate
```

This creates the following tables:

- `users` -- User accounts
- `cache` -- Application cache
- `jobs` -- Queue job tracking
- `personal_access_tokens` -- Sanctum API tokens
- `campaign_blueprints` -- Style rule configurations
- `raw_contents` -- Submitted source content
- `generated_posts` -- AI-generated posts
- `post_versions` -- Version history snapshots
- `agent_conversations` -- Ghostwriter conversation sessions
- `agent_conversation_messages` -- Conversation message history

---

## Queue Setup

The application uses database-backed queues by default.

### Create Queue Tables

```bash
php artisan queue:table
php artisan migrate
```

### Start the Queue Worker

```bash
php artisan queue:work
```

For development with all services running concurrently:

```bash
composer dev
```

This starts the Laravel server, queue worker, Pail log viewer, and Vite dev server simultaneously.

---

## Running the Application Locally

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`.

For the full development experience (server, queue, logs, Vite):

```bash
composer dev
```

---

## Running Tests

```bash
php artisan test
```

Or using Composer:

```bash
composer test
```

Tests use an in-memory SQLite database with synchronous queue execution for speed and isolation.

### Test Suite Summary

| Test File | Coverage |
|---|---|
| `AuthRegistrationTest` | User registration validation and success |
| `AuthLoginLogoutTest` | Login and logout flows |
| `AuthProtectionTest` | Unauthenticated access prevention |
| `CampaignBlueprintTest` | CRUD, ownership, and validation for blueprints |
| `RawContentSubmissionTest` | Content submission, job dispatch, and ownership |
| `GeneratedPostLifecycleTest` | Post listing, viewing, status updates, ownership |
| `GeneratePostFromRawContentJobTest` | Job execution and AI integration |
| `PostVersionHistoryTest` | Version listing and JSON field serialization |
| `GhostwriterConversationTest` | Conversation CRUD and ownership |
| `GhostwriterMessageTest` | Message sending, AI response, and conversation memory |
| `GetCampaignRulesToolTest` | AI tool for retrieving campaign rules |
| `GetPostHistoryToolTest` | AI tool for retrieving post version history |

---

## Code Quality Tools

### Laravel Pint

```bash
./vendor/bin/pint
```

Pint is included in the dev dependencies and enforces consistent code style.

---

## CI/CD Pipeline

The project includes a GitHub Actions workflow at `.github/workflows/ci.yml`.

### Workflow: ThreadForge CI

**Triggers:**
- Every push to any branch
- Pull requests targeting `main`
- Manual dispatch

**Jobs:**

| Job | Runner | Timeout | Steps |
|---|---|---|---|
| Laravel Tests | `ubuntu-latest` | 10 min | Checkout, Setup PHP 8.4, Install Composer dependencies, Prepare Laravel environment, Run tests |

**PHP Extensions:** mbstring, xml, sqlite3, pdo_sqlite

---

## Production Deployment Overview

A typical deployment flow:

```bash
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

### Steps Explained

1. **Pull latest code** -- Fast-forward merge from main.
2. **Install dependencies** -- Composer dependencies without dev packages, with optimized autoloader.
3. **Run migrations** -- Apply any pending database changes.
4. **Optimize** -- Clear and rebuild caches for configuration, routes, and events.
5. **Restart queue workers** -- Ensure workers pick up the new code.

---

## Supervisor Worker Configuration

In production, queue workers should be managed by a process monitor like Supervisor to ensure they stay running.

### Example Supervisor Configuration

```ini
[program:threadforge-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/threadforge-api/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/threadforge/worker.log
stopwaitsecs=3600
```

> **Note:** Supervisor is not included in the repository. This configuration should be set up on your production server as part of deployment.

---

## Example API Workflow

### Complete End-to-End Workflow

**1. Register an account**

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Younes",
    "email": "younes@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**2. Create a Campaign Blueprint**

```bash
curl -X POST http://localhost:8000/api/blueprints \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laravel Technical Posts",
    "target_audience": "Laravel backend developers",
    "tone": "Professional",
    "max_hashtags": 3,
    "max_characters": 280,
    "additional_rules": ["Use concise sentences", "Avoid marketing language"]
  }'
```

**3. Submit raw content**

```bash
curl -X POST http://localhost:8000/api/raw-contents \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "campaign_blueprint_id": 1,
    "content": "Laravel queues allow developers to defer time-consuming tasks like sending emails, processing uploads, or calling external APIs. By moving these operations to background jobs, HTTP responses remain fast and the user experience improves significantly.",
    "source_type": "text"
  }'
```

Response: HTTP `202 Accepted`

**4. Wait for the background job to process**

The `GeneratePostFromRawContentJob` is dispatched to the queue. The worker processes it asynchronously.

**5. Retrieve the generated post**

```bash
curl -X GET http://localhost:8000/api/generated-posts/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**6. Update publication status**

```bash
curl -X PATCH http://localhost:8000/api/generated-posts/1/status \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "publication_status": "posted"
  }'
```

**7. Regenerate the post**

```bash
curl -X POST http://localhost:8000/api/generated-posts/1/regenerate \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**8. View version history**

```bash
curl -X GET http://localhost:8000/api/generated-posts/1/versions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**9. Start a Ghostwriter conversation**

```bash
curl -X POST http://localhost:8000/api/conversations \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Improve my Laravel post"
  }'
```

**10. Send a message to the Ghostwriter**

```bash
curl -X POST http://localhost:8000/api/conversations/CONVERSATION_ID/messages \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Give me three alternative hooks for my latest post."
  }'
```

---

## Example JSON Requests and Responses

### Register

**Request:**

```json
{
  "name": "Younes",
  "email": "younes@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201):**

```json
{
  "message": "Account created successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "Younes",
      "email": "younes@example.com",
      "created_at": "2026-07-07T10:00:00.000000Z"
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

### Create Campaign Blueprint

**Request:**

```json
{
  "name": "Laravel Technical Posts",
  "target_audience": "Laravel backend developers",
  "tone": "Professional",
  "max_hashtags": 3,
  "max_characters": 280,
  "additional_rules": ["Use concise sentences"]
}
```

**Response (201):**

```json
{
  "data": {
    "id": 1,
    "name": "Laravel Technical Posts",
    "target_audience": "Laravel backend developers",
    "tone": "Professional",
    "max_hashtags": 3,
    "max_characters": 280,
    "additional_rules": ["Use concise sentences"],
    "generated_posts_count": 0,
    "created_at": "2026-07-07T10:05:00.000000Z",
    "updated_at": "2026-07-07T10:05:00.000000Z"
  },
  "message": "Campaign blueprint created successfully."
}
```

### Submit Raw Content

**Request:**

```json
{
  "campaign_blueprint_id": 1,
  "content": "Laravel queues allow developers to defer time-consuming tasks...",
  "source_type": "text"
}
```

**Response (202):**

```json
{
  "data": {
    "id": 1,
    "campaign_blueprint_id": 1,
    "campaign_blueprint": {
      "id": 1,
      "name": "Laravel Technical Posts"
    },
    "content_preview": "Laravel queues allow developers to defer time-consuming tasks...",
    "source_type": "text",
    "processing_status": "pending",
    "error_message": null,
    "created_at": "2026-07-07T10:10:00.000000Z",
    "updated_at": "2026-07-07T10:10:00.000000Z"
  },
  "message": "Raw content accepted for async generation."
}
```

### Get Generated Post

**Response (200):**

```json
{
  "data": {
    "id": 1,
    "campaign_blueprint_id": 1,
    "campaign_blueprint": {
      "id": 1,
      "name": "Laravel Technical Posts",
      "tone": "Professional"
    },
    "raw_content_id": 1,
    "raw_content": {
      "id": 1,
      "content_preview": "Laravel queues allow developers to defer time-consuming tasks...",
      "source_type": "text",
      "processing_status": "completed"
    },
    "hook_propose": "Stop blocking your Laravel requests with slow tasks.",
    "body_points": [
      "Move heavy work to queued jobs.",
      "Return responses faster to users."
    ],
    "technical_readability_score": 90,
    "suggested_hashtags": ["#Laravel", "#PHP", "#Backend"],
    "tone_compliance_justification": "The content is concise, technical, and professional.",
    "raw_payload": {
      "source": "laravel_ai",
      "provider": "groq",
      "response": {}
    },
    "publication_status": "draft",
    "created_at": "2026-07-07T10:10:05.000000Z",
    "updated_at": "2026-07-07T10:10:05.000000Z"
  }
}
```

### Update Publication Status

**Request:**

```json
{
  "publication_status": "posted"
}
```

**Response (200):**

```json
{
  "data": {
    "id": 1,
    "publication_status": "posted",
    ...
  }
}
```

### Health Check

**Response (200):**

```json
{
  "status": "ok",
  "message": "ThreadForge API is running"
}
```

---

## Project Structure

```
threadforge-api/
|
+-- app/
|   +-- Ai/
|   |   +-- Agents/
|   |   |   +-- Ghostwriter.php          # Conversational AI agent
|   |   |   +-- PostGenerator.php        # Post generation agent
|   |   +-- Tools/
|   |       +-- GetCampaignRulesTool.php # Tool: retrieve campaign rules
|   |       +-- GetPostHistoryTool.php   # Tool: retrieve post history
|   +-- Enums/
|   |   +-- ProcessingStatus.php         # pending|processing|completed|failed
|   |   +-- PublicationStatus.php        # draft|posted|archived
|   +-- Http/
|   |   +-- Controllers/
|   |   |   +-- Api/
|   |   |       +-- AuthController.php
|   |   |       +-- CampaignBlueprintController.php
|   |   |       +-- GeneratedPostController.php
|   |   |       +-- GhostwriterController.php
|   |   |       +-- PostVersionController.php
|   |   |       +-- RawContentController.php
|   |   +-- Requests/
|   |   |   +-- Auth/
|   |   |   +-- CampaignBlueprint/
|   |   |   +-- GeneratedPost/
|   |   |   +-- Ghostwriter/
|   |   |   +-- RawContent/
|   |   +-- Resources/
|   |       +-- CampaignBlueprintResource.php
|   |       +-- ConversationResource.php
|   |       +-- GeneratedPostResource.php
|   |       +-- MessageResource.php
|   |       +-- PostVersionResource.php
|   |       +-- RawContentResource.php
|   |       +-- UserResource.php
|   +-- Jobs/
|   |   +-- GeneratePostFromRawContentJob.php  # Async post generation
|   +-- Models/
|       +-- CampaignBlueprint.php
|       +-- GeneratedPost.php
|       +-- PostVersion.php
|       +-- RawContent.php
|       +-- User.php
|
+-- config/
|   +-- ai.php                        # AI provider configuration
|
+-- database/
|   +-- migrations/                    # All database migrations
|
+-- routes/
|   +-- api.php                        # API route definitions
|
+-- tests/
|   +-- Feature/                       # 12 feature test files
|   +-- Unit/                          # Empty (placeholder)
|
+-- .github/
    +-- workflows/
        +-- ci.yml                     # GitHub Actions CI pipeline
```

---

## Security Notes

- **Authentication:** All protected endpoints require a valid Sanctum Bearer token.
- **Data Isolation:** Every resource access is verified against the authenticated user's ID. Unauthorized access returns HTTP 404.
- **Input Validation:** All incoming data is validated through dedicated Form Request classes.
- **Password Hashing:** Passwords are automatically hashed using bcrypt.
- **Prompt Injection Protection:** Raw content is treated as untrusted. The AI agent is instructed to extract technical information only, not follow embedded instructions.
- **Environment Variables:** Never commit `.env`, `.env.example` secrets, API keys, or database credentials to version control.
- **APP_KEY:** Generated automatically. Never expose it.
- **Queue Security:** Database queues store job payloads. Ensure the database is secured and not publicly accessible.

---

## Troubleshooting

### Queue jobs are not processing

Ensure the queue worker is running:

```bash
php artisan queue:work
```

Check the `jobs` table for pending or failed jobs.

### AI generation fails with authentication error

Verify your AI provider API key is set correctly in `.env`:

```bash
GROQ_API_KEY=your_actual_key
```

### Migration errors

Ensure the database exists and credentials are correct in `.env`. Then run:

```bash
php artisan migrate:fresh
```

> **Warning:** This drops all tables and re-runs migrations.

### Tests fail

Tests run with SQLite in-memory. Ensure the `pdo_sqlite` extension is installed:

```bash
php -m | grep sqlite
```

---

## Future Improvements

- URL content scraping (fetch and parse content from URLs)
- Batch content processing
- Webhook notifications for job completion
- Rate limiting per user
- Post scheduling and publishing integrations
- Multi-language support for AI generation
- Admin dashboard for monitoring
- API versioning
- OpenAPI/Swagger documentation

---

## Author

**Younes Barrag**
Backend Developer

GitHub: [https://github.com/younesbarrag](https://github.com/younesbarrag)

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
