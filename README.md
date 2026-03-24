# Dostra CRM Lead Ingestion API

Dostra-API is a API-only Laravel application for ingesting lead data from WordPress sites through Zapier webhooks.

## Purpose

Each client website sends lead data through Zapier to this API. Dostra-API stores the lead in the database and exposes simple read endpoints for downstream CRM use.

## Stack

- Laravel 13
- PHP 8.3+
- SQLite for local development
- Pest for testing

This repository does not include a frontend build pipeline. Vite, Tailwind, and starter frontend assets have been removed.

## Flow

WordPress form submission
-> Zapier webhook
-> Laravel endpoint
-> `leads` table
-> API consumers

## Setup

1. Install PHP dependencies.

```bash
composer install
```

2. Create the environment file and application key.

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure SQLite.

```bash
touch database/database.sqlite
```

Set the following in `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

4. Run migrations.

```bash
php artisan migrate
```

5. Start the API server.

```bash
php artisan serve
```

The local server runs at `http://127.0.0.1:8000`.

## Endpoints

### `POST /webhook/leads`

Stores an incoming lead.

Example payload:

```json
{
  "client_id": "client_1",
  "name": "John Smith",
  "email": "john@example.com",
  "message": "Need a quote"
}
```

### `GET /leads`

Returns all leads ordered newest first.

### `GET /leads/{lead}`

Returns a single lead by its database id.

## CSRF

`/webhook/leads` is excluded from CSRF protection in `bootstrap/app.php` so third-party webhook senders can post to it.

## Testing

Run the test suite with:

```bash
php artisan test --compact
```

## Lead Schema

| Field | Type | Notes |
| --- | --- | --- |
| `id` | integer | Primary key |
| `client_id` | string | Client identifier |
| `name` | string nullable | Lead name |
| `email` | string nullable | Lead email |
| `message` | text nullable | Lead message |
| `source` | string nullable | Source label |
| `raw_payload` | json nullable | Full request payload |
| `created_at` | datetime | Created timestamp |
| `updated_at` | datetime | Updated timestamp |

## Current Scope

- Webhook ingestion
- Lead persistence
- Raw payload storage
- Read endpoints for lead retrieval

## Next Steps

- Add request validation with a form request
- Add authentication for trusted webhook clients
- Add filtering or pagination to lead listing
- Introduce a `clients` table if multi-tenant behavior expands
