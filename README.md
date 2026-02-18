# Library App – Půjčovna knih (REST API)

Semestrální práce do 'Programování řízené testy' – REST API pro správu půjčovny knih vyvinuté v Laravel s využitím TDD, Git a CI/CD.

## Popis domény

Aplikace simuluje knihovní systém, kde uživatelé (čtenáři) si mohou půjčovat a rezervovat knihy, a knihovníci spravují katalog. Systém vynucuje business pravidla jako limity výpůjček, pokuty za pozdní vrácení a prioritu rezervací.

### Entity

- **User** – uživatel s rolí `reader` (čtenář) nebo `librarian` (knihovník)
- **Book** – kniha s evidencí celkového a dostupného počtu výtisků
- **Loan** – výpůjčka se stavy `borrowed` → `returned`
- **Reservation** – rezervace se stavy `active` → `fulfilled` / `expired` / `cancelled`

### Business pravidla

1. Uživatel může mít maximálně 3 aktivní výpůjčky současně
2. Knihu nelze půjčit, pokud žádný výtisk není dostupný
3. Uživatel s nezaplacenou pokutou si nemůže půjčit další knihu
4. Při pozdním vrácení se automaticky vypočítá pokuta (10 Kč/den prodlení, lhůta 14 dní)
5. Rezervace expiruje po 3 dnech
6. Knihu nelze půjčit jinému uživateli, pokud na ni existuje aktivní rezervace
7. Validace stavových přechodů – výpůjčku nelze vrátit dvakrát, zrušenou rezervaci nelze znovu aktivovat

## Jak spustit projekt lokálně

### Požadavky

- PHP 8.4+
- Composer
- Git
- SQLite (extension `pdo_sqlite`)

### Instalace
```bash
git clone https://github.com/HasekOn/Library-app.git
cd Library-app
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

### Spuštění serveru
```bash
php artisan serve
# API dostupné na http://localhost:8000/api
```

### Spuštění testů
```bash
# Všechny testy
php artisan test

# S code coverage (vyžaduje Xdebug nebo PCOV)
php artisan test --coverage

# Konkrétní test suite
php artisan test --filter=BookTest
php artisan test --filter=LoanTest
php artisan test --filter=ReservationTest
```

## API Endpointy

### Books (veřejné čtení, zápis jen librarian)

| Metoda | Endpoint          | Popis             | Auth      |
|--------|-------------------|-------------------|-----------|
| GET    | `/api/books`      | Seznam všech knih | Ne        |
| GET    | `/api/books/{id}` | Detail knihy      | Ne        |
| POST   | `/api/books`      | Vytvořit knihu    | Librarian |
| PUT    | `/api/books/{id}` | Upravit knihu     | Librarian |
| DELETE | `/api/books/{id}` | Smazat knihu      | Librarian |

### Loans (vyžaduje přihlášení)

| Metoda | Endpoint                 | Popis                    |
|--------|--------------------------|--------------------------|
| GET    | `/api/loans`             | Moje výpůjčky            |
| POST   | `/api/loans`             | Půjčit knihu (`book_id`) |
| PATCH  | `/api/loans/{id}/return` | Vrátit knihu             |

### Reservations (vyžaduje přihlášení)

| Metoda | Endpoint                        | Popis                        |
|--------|---------------------------------|------------------------------|
| GET    | `/api/reservations`             | Moje rezervace               |
| POST   | `/api/reservations`             | Rezervovat knihu (`book_id`) |
| PATCH  | `/api/reservations/{id}/cancel` | Zrušit rezervaci             |

## Architektura
```
app/
├── Models/              # Eloquent modely (Book, User, Loan, Reservation)
├── Services/            # Business logika (LoanService, ReservationService)
├── Http/
│   ├── Controllers/Api/ # REST API controllery
│   ├── Requests/        # Form Request validace
│   └── Resources/       # API Resource transformace
├── Exceptions/          # Custom výjimky (BusinessRule violations)
└── Notifications/       # Email notifikace (LateReturnNotification)
```

### Vrstvy

- **Controller** – přijímá HTTP request, volá service, vrací JSON response
- **Service** – obsahuje business logiku a pravidla, vyhazuje custom výjimky
- **Model** – Eloquent ORM, vztahy, scopes, jednoduché helper metody
- **Request** – validace vstupů a autorizace (role-based)
- **Resource** – transformace modelu na konzistentní JSON odpověď
- **Exception** – custom výjimky pro business pravidla (409 Conflict)

## Testovací strategie

### Typy testů

**Feature (integrační) testy** – ověřují celý flow od HTTP requestu přes controller, service až po databázi. Používají `RefreshDatabase` trait pro čistý stav databáze v každém testu. Příklady: `BookApiTest`, `LoanApiTest`, `ReservationApiTest`.

**Doménové testy** – testují business pravidla přes service vrstvu s reálnou databází. Příklady: `LoanTest`, `LoanFineTest`, `ReservationTest`.

**Model testy** – ověřují správné chování modelů, factory a helper metod. Příklady: `BookTest`, `UserTest`.

### Mocking a test doubles

- **Notification::fake()** – fake (typ test double) pro emailové notifikace. Důvod: nechceme v testech reálně odesílat emaily, ale chceme ověřit, že se notifikace odešle správnému uživateli se správnými daty.
- **$this->freezeTime() / $this->travel()** – mock systémového času. Důvod: testování pokut a expirace rezervací vyžaduje kontrolu nad časem.
- **$this->actingAs($user)** – stub autentizace. Důvod: simulujeme přihlášeného uživatele bez reálného auth flow.
- **User::factory() / Book::factory()** – factory pattern pro generování testovacích dat s kontrolovanými atributy.

## Bonusová rozšíření

### Statická analýza (Larastan)

Projekt používá Larastan (PHPStan pro Laravel) na úrovni 5 jako quality gate v CI pipeline. Analýza běží automaticky při každém push a pipeline selže, pokud kód obsahuje chyby.
```bash
# Lokální spuštění
vendor/bin/phpstan analyse --memory-limit=512M
```

### Co se nemockuje a proč

Databáze se nemockuje – používáme in-memory SQLite (`DB_DATABASE=:memory:` v `phpunit.xml`), protože chceme ověřit reálnou interakci s databází (ORM, migrace, constraints). Testy jsou díky in-memory DB stále rychlé.

## CI/CD

GitHub Actions pipeline (`.github/workflows/ci.yml`) automaticky při každém push/PR:

1. Nastaví PHP 8.4 + extensions
2. Nainstaluje závislosti (Composer)
3. Připraví prostředí (.env, SQLite, migrace)
4. Spustí všechny testy
5. Vygeneruje code coverage report (artefakt)

### Code Coverage

Cíl: **≥ 70 % line coverage**.

Co se netestuje a proč:
- Framework boilerplate (middleware, providers) – testuje Laravel sám
- Jednoduchý CRUD bez business logiky – pokrytý integračními testy

