<div align="center">

<br/>

```
██████╗  ██████╗ ███████╗████████╗██████╗  █████╗      █████╗ ██████╗ ██╗
██╔══██╗██╔═══██╗██╔════╝╚══██╔══╝██╔══██╗██╔══██╗    ██╔══██╗██╔══██╗██║
██║  ██║██║   ██║███████╗   ██║   ██████╔╝███████║    ███████║██████╔╝██║
██║  ██║██║   ██║╚════██║   ██║   ██╔══██╗██╔══██║    ██╔══██║██╔═══╝ ██║
██████╔╝╚██████╔╝███████║   ██║   ██║  ██║██║  ██║    ██║  ██║██║     ██║
╚═════╝  ╚═════╝ ╚══════╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝    ╚═╝  ╚═╝╚═╝     ╚═╝
```

*Lead ingestion & CRM API backend for the Dostra system*

<br/>

[![Laravel](https://img.shields.io/badge/Laravel_13-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP_8.5+-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Filament](https://img.shields.io/badge/Filament_v5-FDAE4B?style=flat-square&logo=laravel&logoColor=white)](https://filamentphp.com/)
[![Sanctum](https://img.shields.io/badge/Sanctum-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com/docs/sanctum)
[![SQLite](https://img.shields.io/badge/SQLite-003B57?style=flat-square&logo=sqlite&logoColor=white)](https://sqlite.org/)
[![MySQL](https://img.shields.io/badge/-MySQL-4479A1?style=flat-square&logo=mysql&labelColor=4479A1&logoColor=FFF)](https://www.mysql.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Pest](https://img.shields.io/badge/Pest-blue?style=flat-square)](https://pestphp.com/)

<br/>

</div>

---

## Overview

`dostra-api` is a Laravel 13 REST API with two responsibilities:

1. **Webhook ingestion** — receives signed leads from WordPress/WPForms via Zapier, verifies each request, and stores the lead against the correct client
2. **CRM backend** — exposes authenticated endpoints powering a role-separated dashboard for admins and client users

> No frontend build pipeline is included. Vite, Tailwind, and starter assets have been removed.

---

## Architecture

### Webhook Flow

```
WordPress Form  ──▶  Zapier  ──▶  Signed Webhook  ──▶  Laravel API
                                                              │
                                                    HMAC verification
                                                    Client key lookup
                                                    Idempotency check
                                                              │
                                                         leads table
```

### CRM Access Model

| Role | Clients | Users | Leads | Admin Panel |
|------|---------|-------|-------|-------------|
| `super_user` | All | All | All | ✓ (Filament) |
| `admin` | All | All | All | — |
| `client` | Assigned only | — | Assigned clients only | — |

`super_user` and `admin` are both treated as admin in the API (`isAdmin()` returns true for both). Only `super_user` can access the Filament panel at `/admin`.

Access is enforced via Sanctum auth, Laravel policies, and scoped queries.

---

## Stack

| | |
|--|--|
| **Framework** | Laravel 13 |
| **Runtime** | PHP 8.5+ |
| **Auth** | Sanctum personal access tokens |
| **Admin Panel** | Filament v5 (`/admin`, `super_user` only) |
| **Database** | SQLite *(dev)* · MySQL / PostgreSQL *(prod)* |
| **Testing** | Pest |

---

## Setup

### 1 — Install dependencies

```bash
composer install
```

### 2 — Environment & app key

```bash
cp .env.example .env
php artisan key:generate
```

### 3 — Configure SQLite

```bash
touch database/database.sqlite
```

Add to `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

### 4 — Migrate & seed

```bash
php artisan migrate --seed
```

### 5 — Start the server

```bash
php artisan serve
```

API available at `http://127.0.0.1:8000`

---

## Authentication

### CRM Login

```http
POST /api/login
```

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

Returns:

```json
{
  "token": "plain-text-token",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "role": "admin",
    "clients": []
  }
}
```

Use the token on all authenticated requests:

```http
Authorization: Bearer <token>
Accept: application/json
```

### Webhook Authentication

Each webhook request must include four headers:

```http
X-Client-Key:          client_1
X-Webhook-Id:          evt_abc123
X-Webhook-Timestamp:   1711770000
X-Webhook-Signature:   <hmac>
```

Signature is computed as:

```
hash_hmac('sha256', <timestamp>.<raw_json_body>, <client_webhook_secret>)
```

The API additionally enforces timestamp freshness, active client lookup, and idempotency by `X-Webhook-Id`.

---

## Endpoints

### Public

#### `POST /api/webhook/leads`

Ingests a signed lead for the verified client.

<details>
<summary>Example request & response</summary>

<br/>

**Headers:**
```text
X-Client-Key: client_1
X-Webhook-Id: evt_abc123
X-Webhook-Timestamp: 1711770000
X-Webhook-Signature: <hmac>
```

**Body:**
```json
{
  "name": "John Smith",
  "email": "john@example.com",
  "message": "Need a quote"
}
```

**Response `201`:**
```json
{
  "message": "Lead received successfully",
  "data": {
    "id": 1,
    "client_id": 1,
    "name": "John Smith",
    "email": "john@example.com",
    "message": "Need a quote",
    "source": "wpforms-zapier",
    "webhook_event_id": "evt_abc123",
    "created_at": "2026-03-31T00:00:00.000000Z"
  }
}
```

> Duplicate `X-Webhook-Id` values return the existing lead — no duplicate is created.

</details>

---

### Authenticated CRM

> All endpoints below require `Authorization: Bearer <token>`

#### Session

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| `GET` | `/api/me` | All | Authenticated user + assigned clients |
| `POST` | `/api/logout` | All | Revoke current token |

#### Clients

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| `GET` | `/api/clients` | Admin: all · Client: assigned | List clients |
| `POST` | `/api/clients` | Admin | Create a client |
| `GET` | `/api/clients/{client}` | Admin: any · Client: assigned | Get client |
| `PUT` | `/api/clients/{client}` | Admin | Update client |
| `POST` | `/api/clients/{client}/rotate-webhook-secret` | Admin | Rotate webhook secret |

<details>
<summary>Create client body</summary>

<br/>

```json
{
  "name": "Acme Plumbing",
  "client_key": "acme_plumbing",
  "webhook_secret": "optional-min-32-chars",
  "is_active": true
}
```

`client_key` and `webhook_secret` are optional — auto-generated if omitted. Auto-generated `client_key` uses the format `wp_{normalized_name}` (e.g. `wp_acme_plumbing`). Auto-generated `webhook_secret` is a 64-character random string.

> `webhook_secret` is **not** returned in GET responses. Use `POST /api/clients/{client}/rotate-webhook-secret` to retrieve or reset it.

</details>

#### Users

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| `GET` | `/api/users` | Admin | All users with client assignments |
| `POST` | `/api/users` | Admin | Create user |
| `GET` | `/api/users/{user}` | Admin | Get user |
| `PUT` | `/api/users/{user}` | Admin | Update user, role, and assignments |

<details>
<summary>Create user body</summary>

<br/>

```json
{
  "name": "Client User",
  "email": "client@example.com",
  "password": "password123",
  "role": "client",
  "client_ids": [1]
}
```

Valid roles: `super_user`, `admin`, `client`. `client_ids` assigns clients to the user (required for `client` role).

</details>

#### Leads

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| `GET` | `/api/leads` | Admin: all · Client: assigned | Paginated leads |
| `GET` | `/api/leads/{lead}` | Admin: any · Client: assigned | Get lead |

**Query parameters:**

```text
/api/leads?per_page=25
/api/leads?client_id=1
/api/leads?email=john@example.com
/api/leads?search=quote
/api/leads?from=2026-03-01&to=2026-03-31
```

---

## Rate Limits

| Endpoint | Limit |
|----------|-------|
| `POST /api/login` | 5 / minute per email + IP |
| `POST /api/webhook/leads` | 60 / minute per `X-Client-Key` |
| Authenticated CRM | 120 / minute per user |

---

## Data Model

```
┌────────────────┐         ┌──────────────┐       ┌──────────────┐
│   clients      │         │ client_user  │       │    users     │
│────────────────│         │──────────────│       │──────────────│
│ id             │───┐     │ client_id ───┼───┐   │ id           │
│ name           │   └───▶ │ user_id   ───┼┐  │   │ name         │
│ client_key     │         └──────────────┘│  └──▶│ email        │
│ webhook_secret │                         │      │ password     │
│ is_active      │                         └─────▶│ role         │
└────────────────┘                                └──────────────┘
       │
       │ 1:many
       ▼
┌──────────────────┐
│    leads         │
│──────────────────│
│ id               │
│ client_id        │
│ name             │
│ email            │
│ message          │
│ source           │
│ webhook_event_id │
│ raw_payload      │
└──────────────────┘
```

---

## Local Seed Users

| Role | Email | Password |
|------|-------|----------|
| `super_user` | `superuser@example.com` | `password` |
| `admin` | `admin@example.com` | `password` |
| `client` | `client@example.com` | `password` |
| `client` | `client2@example.com` | `password` |

Two seeded clients (`client_1`, `client_2`) are available for local webhook testing. `client@example.com` is assigned to `client_1`; `client2@example.com` to `client_2`.

---

## Testing

Run the full test suite:

```bash
php artisan test --compact
```

Format PHP files:

```bash
vendor/bin/pint --dirty --format agent
```

---

## Current Scope

```
✦  Signed Zapier webhook ingestion
✦  Per-client lead storage
✦  Webhook idempotency
✦  Token-based CRM auth (Sanctum)
✦  Three-tier role system (super_user / admin / client)
✦  Filament admin panel (/admin, super_user only)
✦  Client management with auto-generated keys and secrets
✦  User management
✦  Paginated lead retrieval with filters
✦  Policy-backed API authorisation
✦  Rate limiting
```

---

## 📄 License

Internal use only. Add a licence file if this becomes public.

---

<div align="center">
<sub>Built with Laravel · PHP · Sanctum</sub>
</div>
