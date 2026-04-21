# SecureVote — Cryptographic Voting System

A pedagogical yet production-quality voting backend in **native PHP 8.1+** and **MySQL/InnoDB**.

Demonstrates: SHA-256 chain hashing · ACID transactions · secure auth · PDO · clean MVC.

---

## Architecture

```
voting-system/
├── schema.sql                     ← MySQL DDL (run first)
├── bootstrap.php                  ← PDO singleton, autoloader, helpers
├── .htaccess                      ← Apache security headers + routing
│
├── config/
│   └── database.php               ← DB constants (override via ENV)
│
├── public/
│   └── index.html                 ← Minimal SPA frontend
│
├── src/
│   ├── Repositories/              ← Data Access Layer (PDO only)
│   │   ├── UserRepository.php
│   │   ├── VoteRepository.php     ← getLastHash, insertWithChain
│   │   └── LogRepository.php
│   │
│   ├── Services/                  ← Business Logic Layer
│   │   ├── AuthService.php        ← register, login (bcrypt cost=12)
│   │   ├── VoteService.php        ← SHA-256 chain + TX insertion
│   │   └── AuditService.php       ← Full chain verification
│   │
│   └── Controllers/               ← HTTP layer (thin dispatchers)
│       ├── AuthController.php
│       ├── VoteController.php
│       └── AuditController.php
│
└── api/                           ← Entry points (bootstrap + delegate)
    ├── auth/
    │   ├── register.php           ← POST
    │   ├── login.php              ← POST
    │   └── logout.php             ← POST
    ├── vote/
    │   ├── submit.php             ← POST (auth required)
    │   └── results.php            ← GET  (public)
    └── audit/
        ├── chain.php              ← GET  (public)
        └── logs.php               ← GET  (auth required)
```

---

## Setup

### 1. Database
```bash
mysql -u root -p < schema.sql
```

### 2. Configuration
```bash
# Either edit config/database.php, or set environment variables:
export DB_HOST=localhost
export DB_NAME=voting_system
export DB_USER=your_user
export DB_PASS=your_password
```

### 3. Web Server

**Apache** (with mod_rewrite):
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/voting-system
    AllowOverride All
</VirtualHost>
```

**PHP built-in server** (development only):
```bash
cd voting-system
php -S localhost:8000 -t .
# Then open http://localhost:8000
```

---

## API Endpoints

| Method | Endpoint              | Auth | Description                    |
|--------|-----------------------|------|--------------------------------|
| POST   | `/api/auth/register`  | No   | Register a new user            |
| POST   | `/api/auth/login`     | No   | Login, receive session cookie  |
| POST   | `/api/auth/logout`    | No   | Destroy session                |
| POST   | `/api/vote/submit`    | Yes  | Cast vote (one per user)       |
| GET    | `/api/vote/results`   | No   | Aggregated results             |
| GET    | `/api/audit/chain`    | No   | Full chain + tamper detection  |
| GET    | `/api/audit/logs`     | Yes  | Audit event log                |

### Example Requests
```bash
# Register
curl -X POST http://localhost:8000/api/auth/register.php \
  -H 'Content-Type: application/json' \
  -d '{"username":"alice","email":"alice@example.com","password":"secret123"}'

# Login
curl -c cookies.txt -X POST http://localhost:8000/api/auth/login.php \
  -H 'Content-Type: application/json' \
  -d '{"username":"alice","password":"secret123"}'

# Vote
curl -b cookies.txt -X POST http://localhost:8000/api/vote/submit.php \
  -H 'Content-Type: application/json' \
  -d '{"choice":"candidate_a"}'

# Verify chain integrity
curl http://localhost:8000/api/audit/chain.php
```

---

## Security Design

### Authentication
- `password_hash()` with **bcrypt cost=12**
- `password_verify()` with constant-time comparison
- Dummy hash for timing-safe login failures (prevents user enumeration)
- `session_regenerate_id(true)` on login
- `HttpOnly`, `SameSite=Strict`, `Secure` cookie flags

### One-User-One-Vote
- `UNIQUE(user_id)` constraint in `votes` table (DB-level guarantee)
- Backend `hasVoted()` check inside the same transaction (application-level)
- Both layers must fail for a duplicate vote to succeed — defence in depth

### Cryptographic Chain
```
hash_n = SHA256( user_id | choice | timestamp | hash_{n-1} )
```
- Chain starts with a **genesis hash** (64 zeros) — same pattern as Bitcoin
- `AuditService::verifyChain()` recomputes every hash from stored fields
- Any field mutation (user_id, choice, timestamp, or previous_hash) breaks all subsequent hashes
- `UNIQUE(hash)` prevents hash collision injection

### Database
- **InnoDB** engine for row-level locking and ACID transactions
- All queries via **PDO prepared statements** — zero string interpolation
- `PDO::ATTR_EMULATE_PREPARES = false` forces real prepared statements
- Foreign key `votes.user_id → users.id` with `ON DELETE RESTRICT`

### HTTP Layer
- Security headers (CSP, X-Frame-Options, HSTS-ready, nosniff)
- Method validation on every endpoint
- No raw SQL errors exposed to clients

---

## Extending

- **Add a candidate**: edit `VoteService::VALID_CHOICES`
- **Admin role**: add `role` column to `users`, gate `audit/logs.php` on `role = 'admin'`
- **Rate limiting**: add IP-based throttle in `bootstrap.php` using Redis or a DB table
- **HTTPS**: uncomment `Strict-Transport-Security` in `.htaccess` and set `'secure' => true` in session params
# mini-Voting-System
# mini-Voting-system-
# mini-Voting-system-
