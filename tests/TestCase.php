<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests exercise the final RBAC matrix. Older test fixtures
        // create staff accounts directly, so establish default grants when
        // their in-memory database has been migrated.
        if (Schema::hasTable('roles')) {
            $this->seed(RolePermissionSeeder::class);
        }
    }

    protected function grantStaffPermissions(string ...$slugs): void
    {
        Role::where('slug', 'staff')->firstOrFail()->permissions()->syncWithoutDetaching(
            Permission::whereIn('slug', $slugs)->pluck('id')
        );
    }
}
