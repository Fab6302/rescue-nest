# RescueNest

RescueNest implements the PetConnect brief under the project name chosen for this chat. It uses Laravel 12, Blade, and server-side sessions. MySQL is the project database target. The Windows launcher uses SQLite for a quick local demo.

## Quick start on Windows

1. Extract the entire ZIP.
2. Open the rescue-nest folder and double-click **Start RescueNest.cmd**. The launcher uses XAMPP PHP if found at its default location, or PHP on PATH.
3. Open http://127.0.0.1:8000/ while the terminal remains open.
4. Choose **Join RescueNest** to register your account. The ten supplied photo profiles are visible to visitors. The two Golu and two Mew photos are separate sample records because the names were supplied in photo order.

PHP needs the SQLite driver for the launcher and the MySQL driver for the course setup. The ZIP includes Composer dependencies. To recreate them from the lock file, run `composer install` in the project folder. Use PHP 8.2 or newer.

## MySQL setup

1. Start MySQL or MariaDB in XAMPP and create an empty database named `rescue_nest`.
2. Copy `.env.example` to `.env` in a fresh extraction. Set your database credentials in that local file. A demo already using SQLite keeps its separate database file.
3. From the project folder run the following commands. Use `C:\xampp\php\php.exe` in place of `php` if PHP is not on PATH.

```sh
php artisan key:generate
php artisan migrate
php artisan rescue:demo
php artisan serve --host=127.0.0.1 --port=8000
```

## Make shop and moderator accounts

Choose Shop owner when registering, or select Become a shop owner on Shops from an existing community member account. For moderator access, register an account and assign the role from the terminal. The terminal can also assign shop access:

```sh
php artisan rescue:role shop@example.com shop
php artisan rescue:role moderator@example.com moderator
```

Replace the sample emails with the registered emails. Use a different browser profile or sign out before switching accounts. Visitors and regular member accounts can browse shops and products. Shop accounts create shop profiles, then add products from the shop page. Moderator accounts see the moderation link after login.

## Test the account flow

Register, log out, and log back in with **Keep me signed in**. Open **Account** to edit your name or email and optionally change your password. Account changes require your current password.

Password recovery uses Laravel's mail driver. In the local demo, request a reset and open the newest reset link in `storage/logs/laravel.log`. Configure an SMTP mailer and a real `APP_URL` to deliver links by email outside the demo. Use separate test accounts to check ownership and privacy.

## Source and submission

- `routes/web.php` defines the page routes.
- `app/Http/Controllers` contains account and community actions.
- `app/Models` represents accounts and linked community entries.
- `resources/views` contains Blade pages and components.
- `database/migrations` creates the database tables.
- `docs` contains the supplied SRS, sprint mapping, diagram, and verification record.

The source is local. Create the GitHub repository and invite collaborators through your team's account. The demo SQLite data, uploads, app key, and reset log belong on your device.

## Extended demo directory

The Community page displays the supplied portraits in listed order with Fabin first. The Shops and Products pages include clearly labelled sample listings and stock. To add a shop, use a shop account. Open a shop you own to add products. To load the expanded sample data into an existing database after copying the updated source, run `php artisan rescue:demo`. Keep your existing .env, database, and uploaded files when updating an installation.

## Rescue accounts and cases

On registration, choose Cat parent / community member or Rescuer. Member and rescuer accounts can publish a rescue case with a photo, animal type, area, location, urgency, and description. A rescuer can claim another member's open case, then mark it rescued. Open cases appear by default. Use the status filter to see cases in progress, rescued, or closed. An author can close their own unclaimed case. People can register as a shop owner or open Shops and select Become a shop owner from an existing community member account. Only the moderator role requires the terminal command. From Products, select Add a product and choose a shop you own. Existing accounts and databases work with the updated code.
