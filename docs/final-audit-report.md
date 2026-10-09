# Utopia Bay Resort Management System — final correctness audit

Audit branch: `feature/final-audit`. This report describes the repository code and SQLite in-memory tests; no real MySQL data was migrated, reset, or seeded during this audit.

## Module status

`C/R/U/D` means create, list/detail, update, and delete. `Archive` or `Deactivate` is the deliberate alternative to hard deletion. Route middleware and, where present, policies or ownership checks provide the permission controls described below.

| Module | CRUD / workflow | Validation and permissions | Business rules, issues and audit fixes |
| --- | --- | --- | --- |
| Authentication and customer registration | Register, sign in, sign out, profile/password update | Guest/auth routes; login is role-separated and rate-limited; public registration forces Customer | Active-account check and portal redirects present. No logic change. |
| Staff login | Sign in/out | Staff roles only; inactive accounts rejected | `/staff/login` remains separate from customer `/login`. |
| Staff accounts | C/R/U/D for accounts without history; deactivate otherwise | Admin-only routes; request validation and last-admin/self-protection | Historical activity blocks deletion. |
| Customers | R/U/D for accounts without history; deactivate otherwise | Staff permission mapping; customer-only binding and request validation | Bookings, payments, events, membership, and loyalty history block deletion. No staff-side customer creation by design. |
| Roles and permissions | R/U of manager/staff grants | Admin-only route and protected Admin role; route permissions checked server-side | UI links use the same permission catalog; direct URL access is guarded. |
| Operations dashboard | Read-only operational lists and metrics | Authenticated staff with `view_dashboard` | Payment/revenue and booking/room operations retained. |
| Bookings | C/R/U; controlled Confirm/Cancel/Check In/Check Out; D only without payment or status history | Staff route permissions, active-customer validation, capacity, date and overlap checks | Lifecycle service locks rows and logs transitions; Check In makes room occupied, Check Out makes it cleaning. Audit fix protects status history from hard deletion and disables the list Delete action accordingly. |
| Room types | C/R/U/D where referentially safe | Staff `manage_room_types`; unique name, price/capacity/image validation | Audit fix blocks deletion when any room has booking history and defers uploaded type-image cleanup until after successful deletion. |
| Rooms | C/R/U/D where FK permits | Staff `manage_rooms`; unique room number, room type, rate/status and image validation | Gallery service handles files. A room linked to a booking is protected by the database FK. The legacy `booked` operational status remains a known ambiguity. |
| Room gallery | Upload, R, metadata/cover U, individual D | Staff `manage_room_gallery`, room/image ownership, image type/size/count validation | Primary and legacy-image fallback retained; storage cleanup runs after committed changes. |
| Facilities | C/R/U/D | Staff `manage_facilities` | Audit fix aligns the form and controller with the database's `open`, `closed`, `maintenance` status enum; prior `1`/`0` submissions were invalid for this schema. |
| Packages | C/R/U/D if unused | Staff `manage_packages`; type, price and status validation | Bookings snapshot package prices; linked package deletion blocked. Buffet quantity is guest-capped, single-unit types are fixed at one, `other` is 1–10. |
| Membership types | C/R/U/D if unused | Staff permission; 12-month duration, positive price, discount and threshold validation | Seeder supplies Silver/Gold/Platinum values. Management CRUD intentionally allows changing plan catalog values; the seed defaults are not immutable database constraints. |
| Membership purchases | Customer paid checkout and read status; no free subscription | Customer role/active/ownership; purchase and payment service validation | Paid payment activates annual membership. Refund updates purchase/membership status; no paid upgrade/renewal checkout yet. |
| Loyalty | Read balance/history; service-generated earn/reverse/upgrade | Active paid membership required; staff/customer views are permission/ownership scoped | Booking Paid amount after discount earns floor dollars; membership/event payments excluded; threshold consumption, one-time refund reversal, and no automatic downgrade are present. |
| Payments | Staff C/R/U of Pending, verify, refund Paid, receipt; D Pending only | Staff permissions, customer/booking/event ownership and amount checks; customer checkout is separate | Card is simulated Paid; ABA/KHQR and Cash at Hotel start Pending. Booking amount is server-authoritative and Paid confirms eligible Pending booking. Audit fix routes Booking Details to an existing Payment instead of inviting a duplicate, and removes obsolete Bank Transfer preselection. |
| Payment settings | Read/update singleton, replace/remove custom QR | Staff `manage_payment_settings`; file validation | Default demo QR fallback is retained when no custom QR exists; disabled ABA/KHQR is not offered. |
| Event reservations | Customer C/R/Cancel; staff R/U Pending, Approve/Reject/Cancel, D only unprocessed/unpaid safe records | Customer ownership; staff permission map; venue/date/guest validation | Processed or paid history is protected. Venue overlap and capacity checks remain in the model. |
| Venues | Staff C/R/U/Archive; public R | Staff permission plus Venue policy; image and capacity validation | Archive keeps event history and prevents new reservations. |
| Receipts and confirmations | Read/print | Customer payment ownership; staff payment permission | Booking and payment references, amounts, methods and statuses come from saved records. |

## Routes, navigation and validation

`php artisan route:list --json` loaded successfully. No duplicate named routes or method-plus-URI collisions were found. Customer booking/payment routes use customer role, active-account middleware, and ownership checks. Staff management routes use authentication, staff role, and `EnsureStaffPermission`; staff account and RBAC routes additionally require Admin. Homepage, navbar, footer, and sidebar links were checked against named routes. A search found no empty `href`, `stretched-link`, old product branding, or decorative gradient in the audited application sources.

The stale Booking Details payment actions were an exception: they preselected `Bank Transfer`, which is absent from current new-payment choices, and could offer creation even when a linked payment existed. They now use ABA/KHQR or Cash at Hotel for a new record, and link to the existing Payment when present.

## Schema summary for diagrams

The application migrations define these business tables and relationships:

- `users` owns `bookings`, `payments`, `memberships`, `membership_purchases`, `event_bookings`, and one `loyalty_account`; staff users may be referenced by `booking_status_logs.changed_by` and `event_bookings.processed_by`.
- `roles` ↔ `permissions` through `permission_role`; `users.role` stores the role slug rather than an FK.
- `room_types` → `rooms`; `rooms` ↔ `facilities` through `facility_room`; `rooms` → `room_images` and `bookings`.
- `bookings` → `booking_status_logs`, `booking_packages`, `booking_payment_slips`, and `payments`; `booking_packages` references `packages`. A booking can optionally reference the membership used at creation and also keeps discount snapshots.
- `membership_types` → `memberships` and `membership_purchases`; its optional next-tier FK points back to `membership_types`. A purchase may point to an activated `membership` and has at most one payment.
- `venues` → `event_bookings`; an event reservation may belong to a customer and can have payments. Venue deletion is restricted while reservations exist.
- `payments` belongs to a user and optionally one booking, event reservation, or membership purchase. The single-record `payment_settings` table stores QR/account configuration.
- `loyalty_accounts` belongs uniquely to a user; `loyalty_transactions` belongs to an account and optionally references the source payment and membership. `(payment_id, kind)` is unique to prevent duplicate earn/refund ledger entries.
- Laravel infrastructure tables: `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, and `failed_jobs`.

The two historical payment/booking-FK migrations inspect existing foreign keys before adding one, so the integrated migration does not add a duplicate. Booking/payment, event/payment, and membership-purchase/payment relationships use restrictive deletion where history must survive; gallery files and room-image rows follow their own cleanup path. The database has no cross-column constraint requiring **exactly one** of a Payment's three optional parent FKs; request validation and service/controller paths enforce that for new normal writes. This should be drawn as an application invariant in the ERD.

## Seeder alignment

`DatabaseSeeder` calls roles/permissions, users, rooms/facilities, membership types/packages, venues, demo bookings, payments, and events in dependency order. The focused `DemoSeederTest` passed previously and checks a second run for duplicate-free records. Payment/loyalty activity is generated through services, not hand-written ledger totals. Seed defaults are only added when absent, so existing local catalog edits remain. Demo seeding was **not** run against the local MySQL database in this audit.

## Regression triage

The starting suite reported **186 assertion failures** among 472 tests. The original failing classes grouped as follows (these are disjoint class counts, not estimates of independent bugs):

| Area | Original failing tests | Root cause found |
| --- | ---: | --- |
| Booking validation and lifecycle | 122 | Tests created staff accounts without seeded RBAC defaults; middleware returned 403 before booking assertions ran. |
| Payments, customer payment, settings and slips | 44 | Missing action-specific grants, a changed protected-delete error key, and an obsolete Bank Transfer link assertion. |
| Membership purchases and loyalty | 8 | Staff verification/refund/view tests lacked the corresponding grants; no loyalty calculation defect was found. |
| Event reservations and venues | 5 | Missing delete/manage-venue grants or staff fixture defaults. |
| Dashboard and smoke views | 2 | Missing staff default grant and an old welcome-heading assertion. |
| Room gallery | 1 | Staff upload expected success without `manage_room_gallery`. |
| Module search/filter | 3 | Missing staff defaults; event Delete expected without `delete_event_reservations`. |
| Portal redirect | 1 | Pre-RBAC redirect expectation pointed to Room Management for staff lacking that access; Dashboard is the allowed destination. |

The compact runner also exposed four non-assertion setup errors after the assertion failures were resolved: two tests invoked the **obsolete** pre-shared-demo `PaymentSeeder` without its required demo accounts/bookings and expected its removed arbitrary event payment; one payment test and one venue test tried to read a record after a staff user without the required grant was denied creation. Those tests now use the current demo sequence or explicit grants and still assert data integrity.

`tests/TestCase.php` seeds the idempotent RBAC catalog after an in-memory migration. Tests requiring privileges beyond Front Desk defaults grant only those named permissions in their own fixtures. Assertions for protected booking deletion, the current dashboard heading, ABA/KHQR preselection, and the new demo seeder replace obsolete expectations. No tests were deleted or broadly skipped, and **no production authorization or business logic changed in this regression-triage pass**. The production fixes recorded in the module table were made during the preceding audit pass.

No remaining failure was caused by a SQLite/MySQL SQL or foreign-key difference. The CLI's default PHP configuration lacks GD; the final run loaded the locally installed GD extension for image tests. This does not alter the application's database configuration.

## Known limitations

1. The Rooms status list still accepts `booked`; its operational meaning remains unresolved from earlier module work. The Booking service deliberately requires `available` at check-in and does not reinterpret `booked`.
2. The current audit is code-, route-, and SQLite-test-based. It does not certify real MySQL migration state, filesystem permissions, email delivery, or a complete manual browser walkthrough.
3. Laravel Boost could not be installed as required by repository `AGENTS.md` because Packagist DNS failed. No Composer files were changed.

## Checks performed

- Focused `FinalAuditFixesTest`: **4 passed, 16 assertions**.
- Affected Booking validation/lifecycle and Payment feature groups, then ten smaller affected feature groups, were rerun during triage. The final focused payment/customer/venue group passed **57/57**.
- Final full SQLite in-memory suite with GD explicitly enabled: **472 passed / 472 total, 2,945 assertions, zero failures or errors**.
- `php artisan route:list --json` and duplicate-route checks passed; Blade `view:cache` passed; changed PHP files passed syntax checks; `git diff --check` passed.

## Safe local MySQL verification

First confirm that your local `.env` targets the intended MySQL/MariaDB demo database. The following commands are read-only and do not run migrations or seeders:

```powershell
php artisan about
php artisan migrate:status
php artisan db:show
php artisan route:list
php artisan tinker --execute="dump(['driver' => DB::connection()->getDriverName(), 'users' => DB::table('users')->count(), 'rooms' => DB::table('rooms')->count(), 'bookings' => DB::table('bookings')->count(), 'payments' => DB::table('payments')->count(), 'memberships' => DB::table('memberships')->count(), 'events' => DB::table('event_bookings')->count()]);"
php artisan tinker --execute="dump(DB::table('users')->whereIn('email', ['admin@utopiabay.test', 'manager@utopiabay.test', 'staff@utopiabay.test', 'customer@utopiabay.test'])->get(['email', 'role', 'is_active']));"
```

Do **not** run `migrate:fresh`, reset, or seed against a database with meaningful records. On a separate local demo copy, `php artisan migrate` followed by `php artisan db:seed` is the documented additive setup; both commands write data and should be run only after reviewing the target database. In a browser, use the existing customer and staff portals to verify registration/login, RBAC-denied direct URLs, booking availability and check-in/out room statuses, Card/ABA-KHQR/Cash payment states, membership activation and loyalty/refund history, and event reserve/approve/cancel flows. These browser checks should use disposable demo records.

## Demo and diagram readiness — schema freeze decision

**Schema ready to freeze: YES for the code/schema baseline and ERD/class-diagram drafting.** The customer booking/payment/membership flow, staff operations, RBAC controls, CRUD screens, and demo seed sequence are present, and the full SQLite suite is green. No unresolved schema correctness defect was identified. Complete the read-only local MySQL checks and the manual browser walkthrough above before final deployment or academic sign-off; this audit did not run against the real MySQL database.
