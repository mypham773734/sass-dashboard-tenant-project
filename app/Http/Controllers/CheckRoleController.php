<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

class CheckRoleController extends Controller
{
    public function index()
    {
        // $user = authContext()->getUser();
        // $tenantId = tenantContext()->getId();

        // $role = isset($user->roles) ? $user->roles : [];

        // $permission = isset($user->permissions) ?  $user->permissions : []; 


        // $roleAdmin = Role::where('tenant_id', $tenantId)->where('name', 'admin')->first();

        // $primaryRole = isset($roleAdmin->permissions) ? $roleAdmin->permissions : []; 
        // return response()->json(['roles' => $roles]);

        // Assign role admin for user


        $user = authContext()->getUser();
        $tenantId = tenantContext()->getId();

        $roleAdmin = Role::where('tenant_id', $tenantId)->where('name', 'admin')->first();

        $user->assignRole($roleAdmin);


        return response()->json([
            'oke' => true, 
        ]); 


        // $user = authContext()->getUser();

        // // Step 1: Spatie có load được roles không?
        // $spatieRoles = $user->roles; // eager load qua relationship

        // // Step 2: Qua roles có permissions không?
        // $viaRoles = $user->getPermissionsViaRoles()->pluck('name');

        // // Step 3: Clear cache rồi thử lại
        // app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        // $afterFlush = $user->fresh()->getPermissionNames();

        // return response()->json([
        //     'user_id'              => $user->id,
        //     'spatie_roles'         => $spatieRoles->pluck('name'),       // có role không?
        //     'permissions_via_roles' => $viaRoles,                          // qua roles có perm không?
        //     'after_cache_flush'    => $afterFlush,                        // sau khi xóa cache?
        // ]);


    }
}
