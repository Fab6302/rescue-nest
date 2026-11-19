# Verification

The application uses Laravel 12.73.0. The packaged files were checked with PHP 8.2.12 on Windows and include the dependencies recorded in composer.lock.

## Executed checks

- PHP syntax checks, Blade compilation, route registration, and database migrations.
- The Windows launcher from a fresh extraction, including application key creation and seeding ten photo profiles.
- An HTTP workflow against SQLite covering registration, account editing, login, local password reset, ownership checks, pet filters, updates, threaded comments, saving, adoption handover, shop and product search, ratings, notices, private sightings, and moderation.
- MySQL-driver migrations and demo seeding on MariaDB 10.4.32.

## Repeat the workflow

Start a fresh extraction, wait for the server, and run `node verification/smoke.cjs` in another terminal from the extracted project folder. The check creates records and changes them, so use a disposable demo database. Set `RESCUE_TEST_URL` for a different port, and `RESCUE_TEST_PHP` for a different PHP executable.

The smoke check uses HTTP and inspects responses. For the course demonstration, also walk through the forms visually on a phone and a desktop. Password recovery is tested using the log mailer. SMTP delivery depends on your mail configuration.
