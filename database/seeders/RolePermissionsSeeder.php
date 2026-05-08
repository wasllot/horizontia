<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createRoles();
    }

    public function createRoles(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('tutor');
        Role::findOrCreate('student');
    }
}
