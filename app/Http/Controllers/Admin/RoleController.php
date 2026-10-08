<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Support\RbacCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::withCount('permissions')->whereIn('slug',['admin','manager','staff'])->orderBy('id')->get();
        $selected = $roles->firstWhere('slug', $request->query('role', 'admin')) ?? $roles->first();
        $selected?->load('permissions');
        return view('admin.roles.index', ['roles'=>$roles,'selected'=>$selected,'groups'=>Permission::orderBy('id')->get()->groupBy('group_name'), 'adminOnly'=>RbacCatalog::ADMIN_ONLY]);
    }
    public function update(Request $request, Role $role)
    {
        abort_unless($request->user()->isAdmin(),403);
        $data = $request->validate(['permissions'=>['sometimes','array'], 'permissions.*'=>['string','distinct','exists:permissions,slug']]);
        $slugs = $data['permissions'] ?? [];
        abort_if(array_intersect($slugs,RbacCatalog::ADMIN_ONLY),422,'Account administration is reserved for Admin.');
        DB::transaction(function () use ($role,$slugs) {
            $locked = Role::lockForUpdate()->findOrFail($role->id);
            abort_if($locked->is_protected || !in_array($locked->slug,['manager','staff'],true),403,'Administrator access is protected.');
            $locked->permissions()->sync(Permission::whereIn('slug',$slugs)->pluck('id'));
        });
        return redirect()->route('admin.roles.index',['role'=>$role->slug])->with('success','Role permissions updated.');
    }
}
