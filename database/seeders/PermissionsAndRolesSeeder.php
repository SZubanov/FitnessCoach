<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsAndRolesSeeder extends Seeder
{

    private array $permissions;
    private array $roles;

    private array $userPermissions = [
        Permission::SHOW_USER,
        Permission::CREATE_USER,
        Permission::UPDATE_USER,
        Permission::DELETE_USER
    ];

    public function __construct()
    {
        $this->permissions = array_merge($this->userPermissions);
        $this->roles = Role::$roles;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createPermissions();
        $this->createRoles();
        $this->assignPermissions();
    }

    private function createPermissions(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::updateOrCreate([
                'name' => $permission
            ]);
        }
    }

    private function createRoles(): void
    {
        foreach ($this->roles as $role) {
            Role::updateOrCreate([
                'name' => $role
            ]);
        }
    }

    private function assignPermissions(): void
    {
        $this->assignAdminPermissions();
    }

    private function assignAdminPermissions(): void
    {
        Role::whereName(Role::ADMIN_ROLE)->first()->givePermissionTo(
            array_merge(
                $this->userPermissions
            )
        );
    }
}
