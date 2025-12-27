<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_access_admin_page()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/admin/api/settings');
        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_admin_page()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'settings.view']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);
        $response = $this->actingAs($user)->get('/admin/api/settings');
        $response->assertStatus(200);
    }
}
