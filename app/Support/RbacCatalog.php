<?php

namespace App\Support;

class RbacCatalog
{
    public const GROUPS = [
        'Dashboard' => ['view_dashboard'],
        'Booking Operations' => ['view_bookings', 'create_bookings', 'edit_bookings', 'delete_bookings', 'cancel_bookings', 'confirm_bookings', 'check_in_guests', 'check_out_guests'],
        'Rooms & Facilities' => ['view_rooms', 'manage_rooms', 'manage_room_types', 'manage_facilities', 'manage_room_gallery'],
        'Payments' => ['view_payments', 'manage_payments', 'verify_payments', 'refund_payments', 'manage_payment_settings'],
        'Events & Venues' => ['view_event_reservations', 'edit_event_reservations', 'approve_event_reservations', 'reject_event_reservations', 'cancel_event_reservations', 'delete_event_reservations', 'view_venues', 'manage_venues'],
        'Membership & Packages' => ['view_memberships', 'manage_membership_types', 'manage_packages', 'view_loyalty_activity'],
        'Customer Management' => ['view_customers', 'manage_customers', 'delete_customers'],
        'Administration' => ['manage_users', 'assign_roles', 'manage_roles_permissions'],
    ];
    public const ADMIN_ONLY = ['manage_users', 'assign_roles', 'manage_roles_permissions', 'delete_customers'];

    public static function permissions(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }

    public static function defaults(string $role): array
    {
        if ($role === 'manager') {
            return array_values(array_diff(self::permissions(), [...self::ADMIN_ONLY, 'manage_payment_settings']));
        }
        if ($role === 'staff') {
            return ['view_dashboard', 'view_bookings', 'create_bookings', 'edit_bookings', 'cancel_bookings', 'confirm_bookings', 'check_in_guests', 'check_out_guests', 'view_rooms', 'view_payments', 'verify_payments', 'view_event_reservations', 'edit_event_reservations', 'approve_event_reservations', 'reject_event_reservations', 'cancel_event_reservations', 'view_venues', 'view_customers'];
        }
        return [];
    }

    public static function allowsRoute(\App\Models\User $user, ?string $route): bool
    {
        $permission = self::routePermission($route);
        if ($route === 'management.rooms.edit' && $user->hasPermission('manage_room_gallery')) {
            return true;
        }
        return $permission !== null && $user->hasPermission($permission);
    }

    /** One mapping used by route middleware and permission-aware links/actions. */
    public static function routePermission(?string $name): ?string
    {
        if ($name === 'dashboard') { return 'view_dashboard'; }
        if (str_starts_with((string) $name, 'management.customers.')) {
            return match (substr($name, strlen('management.customers.'))) {
                'index', 'show' => 'view_customers',
                'edit', 'update' => 'manage_customers',
                'destroy' => 'delete_customers',
                default => null,
            };
        }
        foreach (['admin.users.'=>'manage_users', 'admin.roles.'=>'manage_roles_permissions', 'payment-settings.'=>'manage_payment_settings', 'management.room-types.'=>'manage_room_types', 'management.facilities.'=>'manage_facilities', 'admin.packages.'=>'manage_packages', 'management.rooms.images.'=>'manage_room_gallery'] as $prefix=>$permission) {
            if (str_starts_with((string)$name, $prefix)) { return $permission; }
        }
        if (str_starts_with((string)$name, 'admin.membership-types.')) {
            return $name === 'admin.membership-types.index' ? 'view_memberships' : 'manage_membership_types';
        }
        foreach (['bookings.'=>'booking', 'payments.'=>'payment', 'management.rooms.'=>'room', 'management.venues.'=>'venue', 'management.event-reservations.'=>'event'] as $prefix=>$module) {
            if (!str_starts_with((string)$name, $prefix)) { continue; }
            $action = substr($name, strlen($prefix));
            return match($module) {
                'booking' => match($action) {
                    'index','show'=>'view_bookings', 'create','store'=>'create_bookings', 'edit','update'=>'edit_bookings', 'destroy'=>'delete_bookings', 'confirm'=>'confirm_bookings', 'cancel'=>'cancel_bookings', 'check-in'=>'check_in_guests', 'check-out'=>'check_out_guests', default=>null,
                },
                'payment' => match($action) {
                    'index','show','receipt'=>'view_payments', 'verify'=>'verify_payments', 'refund'=>'refund_payments', 'create','store','edit','update','destroy'=>'manage_payments', default=>null,
                },
                'room' => in_array($action,['index','show'],true) ? 'view_rooms' : 'manage_rooms',
                'venue' => in_array($action,['index','show'],true) ? 'view_venues' : 'manage_venues',
                'event' => match($action) {
                    'index','show'=>'view_event_reservations', 'edit','update'=>'edit_event_reservations', 'destroy'=>'delete_event_reservations', 'approve'=>'approve_event_reservations', 'reject'=>'reject_event_reservations', 'cancel'=>'cancel_event_reservations', default=>null,
                },
            };
        }
        return null;
    }
}
