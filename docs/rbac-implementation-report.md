# Staff RBAC implementation

Branch: `feature/roles-permissions`. No commit, push, merge or branch switch.

## Database and deployment

New migration: `2026_10_10_000001_create_staff_rbac_tables.php`.
Adds `roles`, `permissions`, and `permission_role` (composite primary key and foreign keys).
No existing tables/columns or user role values are rewritten. `users.role` is already VARCHAR(20);
User.staffRole joins that canonical slug to roles.slug. Customers are excluded from the staff role catalog.
The real database has not been migrated or seeded during this task.

Run the additive migration, then only the dedicated safe seeder:

```sh
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

Do not use migrate:fresh/reset. The dedicated seeder does not create or reset user accounts.
Admin bypasses permission pivots; Manager/Staff defaults initialize once. Re-seeding retains custom permissions,
including a deliberately empty permission set. Seeding is idempotent. Deploy migration and seeder together;
uninitialized staff permission tables do not grant access.

## Roles and defaults

- Admin: all permissions automatically, including future permission checks; protected and not editable/revocable.
- Manager: broad operational access (30 permissions), excluding Administration and payment settings.
- Staff / Front Desk: 17 permissions: dashboard; view/create/edit/cancel/confirm bookings; check in/out;
  view rooms/payments/venues; verify payments; view/edit/approve/reject/cancel event reservations.
- Customer: existing customer-only registration, authentication and ownership rules.

34 permissions in seven groups: Dashboard; Booking Operations; Rooms & Facilities; Payments;
Events & Venues; Membership & Packages; Administration.
Additional action permissions distinguish booking deletion, payment CRUD, event cancellation, and venue viewing.
Admin can grant/revoke Manager/Staff operational permissions, including refunds and payment settings.
User/role administration remains Admin-only even if a pivot is manipulated.
Loyalty visibility controls the existing payment-detail loyalty panel, not a new list or loyalty workflow.

## Backend enforcement

- EnsureStaffPermission applies to existing staff route groups and fails closed on unmapped routes.
- RbacCatalog supplies one route/action permission map, Gates, and UI access decisions.
- User.hasPermission reads current database permissions without persistent caching.
- Event and Venue policies preserve workflow/status conditions and customer ownership.
- Manager is recognized by staff login/logout, active-role checks, portal redirects, and booking operations.
- Payment CRUD cannot submit Paid without verify_payments; refund permission is independently enforced.
- Gallery-only permission can open the existing room photo editor but cannot update room details.
- Existing registration forces Customer; profile updates ignore submitted role/permission values.

New routes:
- GET /admin/roles: admin.roles.index
- PATCH /admin/roles/{role}: admin.roles.update
Both require authentication, active Admin role, and management permission enforcement.

## Accounts and history

Existing Users CRUD supports Manager, Staff, Admin and legacy Customer accounts. Add Staff copy clarifies the portal.
Passwords retain existing confirmation/letters/numbers rules and hashing; existing passwords never render.
Optional blank edit password retains the current password. Self-demotion/deactivation/deletion remains blocked.
Transactions/locks protect the last active Admin. Customers/Managers/Staff cannot assign roles.
Deletion blocks booking operator logs, event processing attribution, loyalty accounts and linked bookings,
payments, memberships, purchases or reservations. Staff can be deactivated instead without deleting history.
Existing Payment rows do not retain a separate verifier/refund actor. No historical provenance was fabricated;
this RBAC task does not introduce a new payment audit subsystem.

## UI

Roles & Permissions uses a compact role selector and grouped switches, with protected Admin and reserved
Administration controls disabled. Forms work without JavaScript. Sidebar links and module actions follow
permissions; existing CRUD/status conditions remain. Dashboard financial/detail lists and loyalty panels
respect their module viewing permissions. Management styling remains warm gold/navy and responsive.

## Verification

Focused RBAC + existing Auth/User tests: 21 passed, 118 assertions, using SQLite memory only.
Compiled Blade syntax checked; git diff --check passed. Full suite intentionally not run.
Existing non-RBAC staff tests may need seeded role fixtures or authorized roles when the full suite is approved;
old blanket Staff CRUD expectations are intentionally superseded by these defaults.

Manual browser checks:
1. Run migration and dedicated seeder; log in as Admin and review all three roles.
2. Create Manager/Staff accounts and log in through Staff Login; customer login must reject those accounts.
3. Revoke/grant an operational permission and reload the other user's page; links/actions and direct URL access change.
4. Test refunds, payment settings and user administration restrictions; Admin remains protected.
5. Check gallery-only access, grouped switches, keyboard focus and mobile stacking.
6. Attempt deletion of a historical staff account; verify the explanatory message and deactivation alternative.
7. Verify customer registration, booking/payment navigation, and customer event cancellation manually.

## Files modified

- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/PaymentController.php`
- `app/Http/Middleware/RoleMiddleware.php`
- `app/Http/Requests/StoreUserRequest.php`
- `app/Http/Requests/UpdateEventReservationRequest.php`
- `app/Http/Requests/UpdateEventReservationStatusRequest.php`
- `app/Http/Requests/UpdateUserRequest.php`
- `app/Models/User.php`
- `app/Policies/EventBookingPolicy.php`
- `app/Policies/VenuePolicy.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/BookingService.php`
- `app/Support/PortalRedirect.php`
- `database/seeders/DatabaseSeeder.php`
- `public/css/hotel.css`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/dashboard/_bookings.blade.php`
- `resources/views/admin/dashboard/_operations.blade.php`
- `resources/views/admin/membership_types/_form.blade.php`
- `resources/views/admin/membership_types/create.blade.php`
- `resources/views/admin/membership_types/edit.blade.php`
- `resources/views/admin/membership_types/index.blade.php`
- `resources/views/admin/packages/_form.blade.php`
- `resources/views/admin/packages/create.blade.php`
- `resources/views/admin/packages/edit.blade.php`
- `resources/views/admin/packages/index.blade.php`
- `resources/views/admin/users/_form.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/bookings/create.blade.php`
- `resources/views/bookings/edit.blade.php`
- `resources/views/bookings/index.blade.php`
- `resources/views/bookings/show.blade.php`
- `resources/views/facilities/_form.blade.php`
- `resources/views/facilities/create.blade.php`
- `resources/views/facilities/edit.blade.php`
- `resources/views/facilities/index.blade.php`
- `resources/views/facilities/show.blade.php`
- `resources/views/management/event-reservations/_record-actions.blade.php`
- `resources/views/management/event-reservations/edit.blade.php`
- `resources/views/management/event-reservations/index.blade.php`
- `resources/views/management/event-reservations/show.blade.php`
- `resources/views/management/venues/_form.blade.php`
- `resources/views/management/venues/create.blade.php`
- `resources/views/management/venues/edit.blade.php`
- `resources/views/management/venues/index.blade.php`
- `resources/views/management/venues/show.blade.php`
- `resources/views/partials/management-sidebar.blade.php`
- `resources/views/partials/navbar.blade.php`
- `resources/views/payments/_form.blade.php`
- `resources/views/payments/create.blade.php`
- `resources/views/payments/edit.blade.php`
- `resources/views/payments/index.blade.php`
- `resources/views/payments/settings.blade.php`
- `resources/views/payments/show.blade.php`
- `resources/views/room_types/_form.blade.php`
- `resources/views/room_types/create.blade.php`
- `resources/views/room_types/edit.blade.php`
- `resources/views/room_types/index.blade.php`
- `resources/views/room_types/show.blade.php`
- `resources/views/rooms/_gallery-management.blade.php`
- `resources/views/rooms/create.blade.php`
- `resources/views/rooms/edit.blade.php`
- `resources/views/rooms/index.blade.php`
- `resources/views/rooms/show.blade.php`
- `routes/events.php`
- `routes/web.php`
- `tests/Feature/AuthTest.php`

## Files created

- `app/Http/Controllers/Admin/RoleController.php`
- `app/Http/Middleware/EnsureStaffPermission.php`
- `app/Models/Permission.php`
- `app/Models/Role.php`
- `app/Support/RbacCatalog.php`
- `database/migrations/2026_10_10_000001_create_staff_rbac_tables.php`
- `database/seeders/RolePermissionSeeder.php`
- `resources/views/admin/roles/index.blade.php`
- `tests/Feature/RbacTest.php`
- `docs/rbac-implementation-report.md`
