<?php
namespace Database\Seeders;
use App\Models\Permission;
use App\Models\Role;
use App\Support\RbacCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $newPermissions = [];
            foreach (RbacCatalog::GROUPS as $group => $slugs) {
                foreach ($slugs as $slug) {
                    $permission = Permission::updateOrCreate(['slug'=>$slug], ['name'=>ucwords(str_replace('_',' ',$slug)), 'group_name'=>$group, 'description'=>'Allows '.str_replace('_',' ',$slug).'.']);
                    if ($permission->wasRecentlyCreated) { $newPermissions[$slug] = $permission->id; }
                }
            }
            foreach (['admin'=>['Admin','Full protected administration.'], 'manager'=>['Manager','Broad hotel operational access.'], 'staff'=>['Staff / Front Desk','Daily guest and reservation operations.']] as $slug=>[$name,$description]) {
                $role = Role::firstOrCreate(['slug'=>$slug], ['name'=>$name,'description'=>$description,'is_protected'=>$slug==='admin']);
                if ($slug === 'admin') { $role->update(['is_protected'=>true]); }
                // Seed defaults once; an intentionally empty customized role remains empty.
                if ($role->wasRecentlyCreated && $slug !== 'admin') {
                    $role->permissions()->sync(Permission::whereIn('slug',RbacCatalog::defaults($slug))->pluck('id'));
                } elseif ($slug !== 'admin') {
                    // Grant only newly introduced defaults; preserve later administrator revocations.
                    $role->permissions()->syncWithoutDetaching(array_values(array_intersect_key($newPermissions, array_flip(RbacCatalog::defaults($slug)))));
                }
            }
        });
    }
}
