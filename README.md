# Spese — personal finance tracker

[![tests](https://github.com/ailtonjunior-sd/spese/actions/workflows/tests.yml/badge.svg)](https://github.com/ailtonjunior-sd/spese/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Spese is a free and open-source personal finance app.** Track accounts, income and expenses month by month, with a dashboard that shows where the money went. You can self-host it, study the code, fork it and build on it.

**Stack:** Laravel 13 · Inertia 3 · Vue 3 + TypeScript · Tailwind 4 · Pest · PHPStan (level 7)

![Dashboard](docs/screenshots/dashboard-light.png)

<table>
  <tr>
    <td><img src="docs/screenshots/transactions.png" alt="Transactions" /></td>
    <td><img src="docs/screenshots/tx-form.png" alt="New transaction" /></td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/dashboard-dark.png" alt="Dark mode" /></td>
    <td><img src="docs/screenshots/accounts.png" alt="Accounts" /></td>
  </tr>
</table>

## Features

- Accounts (checking, savings, credit card, cash, investment) with live balances
- Income/expense transactions with categories, paid/pending status and notes
- Monthly dashboard: balance, income vs. expenses (6-month chart), spending by category
- Filters by type, account, category and text search
- Default categories created on sign-up; per-user authorization on every record
- Money stored as integer cents; light and dark mode; responsive

## Running locally

```bash
composer setup
php artisan migrate:fresh --seed   # demo user: demo@example.com / password
composer dev
```

## Quality

```bash
composer test   # Pint + PHPStan + Pest
```

## Open source

Spese is open source under the [MIT License](LICENSE): you are free to use, modify and distribute it, including for commercial purposes.

Contributions are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md) to get started, or open an [issue](https://github.com/ailtonjunior-sd/spese/issues) to report a bug or suggest a feature.

## License

Released under the [MIT License](LICENSE). © 2026 Ailton Junior.
