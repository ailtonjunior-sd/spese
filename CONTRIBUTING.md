# Contributing to Spese

Thanks for your interest in Spese! Bug reports, ideas and pull requests are all welcome.

## Reporting bugs and suggesting features

Open an [issue](https://github.com/ailtonjunior-sd/spese/issues) with:

- what you expected to happen and what happened instead
- steps to reproduce (for bugs)
- screenshots, if the problem is visual

## Development setup

Requirements: PHP 8.3+, Composer, Node 22+.

```bash
git clone https://github.com/ailtonjunior-sd/spese.git
cd spese
composer setup
php artisan db:seed   # optional: demo@example.com / password
composer dev
```

## Pull requests

1. Fork the repository and create a branch from `main`.
2. Add or update tests for your change.
3. Make sure all checks pass before opening the PR:

    ```bash
    composer ci:check   # formatting, lint, types (PHP + TS) and tests
    ```

4. Keep PRs focused: one feature or fix per PR, with a short description of what and why.

By contributing, you agree that your contributions are licensed under the [MIT License](LICENSE).
