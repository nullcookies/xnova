<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeed extends Seeder
{
	public function run ()
	{
		$role = Role::create(['name' => 'admin']);
		//$role->givePermissionTo('users_manage');
	}
}