# Investment Account API

A Laravel backend that maintains an append-only ledger of financial movements for investment clients: deposits,
withdrawals, and buying/selling instruments. Cash balance is maintained transactionally on the client for fast,
concurrency-safe balance checks, while portfolio holdings are always derived from the transaction ledger.

## Requirements

- PHP 8.3+ (with the `bcmath` extension)
- Composer
- MySQL 8.0+ (or compatible)

## Installation

```bash
git clone https://github.com/drilonsaiti/investment-account.git
cd investment-account
composer install
cp .env.example .env
php artisan key:generate
```

Create the database in MySQL:

```sql
CREATE
DATABASE investment_account CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Configure `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=investment_account
DB_USERNAME=root
DB_PASSWORD=
```

Migrate and seed:

```bash
php artisan migrate --seed
```

Start the server:

```bash
php artisan serve
```

The API is now available at `http://127.0.0.1:8000/api`.

## Tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database (configured in `phpunit.xml`), so they never touch your MySQL development
database.

## Seed data

After `migrate --seed`, the system comes with three clients ready for manual testing:

| Client       | Cash   | Holdings          |
|--------------|--------|-------------------|
| Ana (id 1)   | 860.00 | AAPL: 2           |
| Marko (id 2) | 800.00 | TSLA: 10, MSFT: 5 |
| Elena (id 3) | 0.00   | (empty)           |

## API

All responses are JSON. Rate limit: 60 requests/minute per IP.

### `GET /api/clients`

List of clients (paginated).

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "name": "Ana",
            "cash_balance": "860.00"
        }
    ],
    "links": {
        "...": "..."
    },
    "meta": {
        "...": "..."
    }
}
```

### `GET /api/clients/{id}`

Basic details of a single client.

**Response:**

```json
{
    "data": {
        "id": 1,
        "name": "Ana",
        "cash_balance": "860.00"
    }
}
```

### `GET /api/clients/{id}/account`

Full account state: cash balance + current holdings (derived from the ledger).

**Response:**

```json
{
    "data": {
        "client": {
            "id": 1,
            "name": "Ana"
        },
        "cash_balance": "860.00",
        "holdings": [
            {
                "instrument": "AAPL",
                "quantity": 2
            }
        ]
    }
}
```

### `GET /api/clients/{id}/transactions`

Client's movement history (paginated, most recent first).

**Optional query filters:** `?type=buy` (deposit/withdrawal/buy/sell) and
`?instrument=AAPL` — combinable, e.g. `?type=sell&instrument=AAPL`.

### `POST /api/clients/{id}/transactions`

Creates a new movement. The ledger is append-only — there is no update/delete endpoint.

**Fields:**

- `type`: `deposit` | `withdrawal` | `buy` | `sell` (required)
- `amount`: positive number — only for `deposit`/`withdrawal`
- `instrument`, `quantity`, `price_per_unit` — only for `buy`/`sell`

Mismatched field combinations (e.g. `instrument` on a `deposit`) are rejected explicitly.

**Example valid request (buy):**

```json
POST /api/clients/1/transactions
{
    "type": "buy",
    "instrument": "AAPL",
    "quantity": 5,
    "price_per_unit": 100
}
```

**Successful response (201):**

```json
{
    "data": {
        "id": 12,
        "type": "buy",
        "amount": "500.00",
        "instrument": "AAPL",
        "quantity": 5,
        "price_per_unit": "100.0000",
        "created_at": "2026-09-04T12:00:00+00:00"
    }
}
```

**Example rejected request (insufficient cash):**

```json
POST /api/clients/1/transactions
{
    "type": "buy",
    "instrument": "AAPL",
    "quantity": 999,
    "price_per_unit": 100
}
```

**Response (422):**

```json
{
    "message": "The client does not have enough cash for this transaction."
}
```

For more request/response examples covering validation and business-rule edge cases, see the automated test suite in
`tests/Feature/`.

## Business rules

- **Cash never negative** — WITHDRAWAL and BUY fail if cash is insufficient.
- **No short selling** — SELL fails if the client doesn't own enough of the instrument.
- **Immutable ledger** — transactions are only ever appended, never changed or deleted (there is no PUT/DELETE route at
  all).
- **Client isolation** — every client is fully independent from every other client.
- Rejected operations **leave no trace** — no row is written, and state remains identical to before the attempt.

## Why this approach

I keep the cash balance as a stored column on the client, rather than deriving it from the ledger on every read. This
avoids recalculating the full cash ledger for every transaction, and gives concurrent operations a single row to
serialize against — the database transaction then keeps the balance update and the ledger entry atomic. Holdings, on the
other hand, are always derived from the ledger, because they're multi-dimensional (a new instrument per client at any
time) and a separate table for them would add synchronization risk without a real benefit at this scale.

I chose pessimistic locking (`lockForUpdate()`) over optimistic locking (a version column + retry-on-conflict) for
concurrency control. Optimistic locking scales better under high read/low-write contention, but for financial writes I'd
rather a second request wait briefly than fail and need a retry — correctness and simplicity win over throughput at this
scale, and the number of concurrent writes per single client account is realistically very low.

For monetary values I use `bccomp`/`bcmul` instead of native PHP operators, because decimals cast by Eloquent remain
strings, and comparing/multiplying them directly would silently convert them to floats — risking precision errors on
real money.

One subtlety worth noting: `bcmul()` truncates rather than rounds at the given scale (e.g. `3 × 33.333 = 99.999`
truncates to `99.99`, not `100.00`). I kept this default rather than adding explicit rounding logic, since the spec
doesn't define a rounding policy and truncation is a defensible, consistent choice for a broker-style system (never
rounds in the client's favor). This would be worth clarifying with the business before a production release.

The business logic lives in `CreateTransactionAction`, not in the controller, because checking cash/holdings requires
reading current state inside a locked transaction — something that doesn't belong in the HTTP layer and needs to be
testable on its own.

Form validation (Form Request) is clearly separated from business validation (Action + Exceptions): the first checks
whether the shape of the request makes sense, the second checks whether the client's current state allows that
operation.

I kept the system deliberately small — no authentication, caching, or extra observers — because none of that was
requested, and it would pull focus away from ledger correctness, which I believe is the actual point of this exercise.
Things I'd consider for a real production system: authentication with role-based access (e.g. Sanctum), notifications
(email/webhook) when a transaction is large or gets rejected, full audit logging of every change, and testing
row-locking under real concurrency rather than just in-memory SQLite as the current tests do.
