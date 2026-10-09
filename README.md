# Utopia Bay Resort Management System

Utopia Bay is a Laravel application for resort guests and hotel staff. Guests can explore rooms, facilities, and venues; make reservations; choose payment methods; and manage memberships. Staff use the management portal for hotel operations, payments, events, and customer records.

The customer site starts at `/`. Staff sign in at `/staff/login`.

## Shared demo data

After configuring and migrating a **local demo database**, run `php artisan db:seed` to add Utopia Bay sample accounts, rooms, facilities, packages, membership plans, bookings, payments, loyalty activity, and event reservations. Re-running the seeder keeps existing demo records and local edits; it does not reset the database. Do not run this on production or on a database containing real guest data.

All new demo accounts use the password `UtopiaDemo2026!`:

| Portal | Email |
| --- | --- |
| Admin | `admin@utopiabay.test` |
| Manager | `manager@utopiabay.test` |
| Staff | `staff@utopiabay.test` |
| Customer with Silver membership | `customer@utopiabay.test` |
| Customer with checked-in stay | `stay@utopiabay.test` |
| Customer with completed stay | `history@utopiabay.test` |

The booking and payment examples use simulated Card payments and one Cash at Hotel payment. They do not charge a real card. Existing accounts with these email addresses keep their current passwords and details.

## Local setup

Copy `.env.example` to `.env`, configure the local database, then install PHP dependencies with Composer. Generate an application key and run the project's normal Laravel setup commands for your environment. Keep credentials in `.env`, not in this repository.
