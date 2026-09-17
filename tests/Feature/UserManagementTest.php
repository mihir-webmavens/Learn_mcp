<?php

namespace Tests\Feature;

use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_users_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('users.index'))->assertOk();
    }

    public function test_users_are_listed_and_can_be_searched(): void
    {
        $this->actingAs(User::factory()->create());

        User::factory()->create(['name' => 'Alice Smith']);
        User::factory()->create(['name' => 'Bob Jones']);

        Livewire::test(Index::class)
            ->assertSee('Alice Smith')
            ->assertSee('Bob Jones')
            ->set('search', 'Alice')
            ->assertSee('Alice Smith')
            ->assertDontSee('Bob Jones');
    }

    public function test_user_can_be_created(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', 'New User')
            ->set('email', 'new@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'new@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('New User', $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_create_requires_valid_data(): void
    {
        $existing = User::factory()->create();

        $this->actingAs($existing);

        Livewire::test(Index::class)
            ->call('create')
            ->set('name', '')
            ->set('email', $existing->email)
            ->set('password', 'password')
            ->set('password_confirmation', 'different')
            ->call('save')
            ->assertHasErrors(['name', 'email', 'password']);
    }

    public function test_user_can_be_updated_without_changing_password(): void
    {
        $this->actingAs(User::factory()->create());

        $user = User::factory()->create();
        $originalPassword = $user->password;

        Livewire::test(Index::class)
            ->call('edit', $user->id)
            ->assertSet('name', $user->name)
            ->set('name', 'Updated Name')
            ->set('email', 'updated@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertEquals($originalPassword, $user->password);
    }

    public function test_user_password_can_be_updated(): void
    {
        $this->actingAs(User::factory()->create());

        $user = User::factory()->create();

        Livewire::test(Index::class)
            ->call('edit', $user->id)
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_user_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $user = User::factory()->create();

        Livewire::test(Index::class)
            ->call('confirmDelete', $user->id)
            ->call('delete');

        $this->assertNull($user->fresh());
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(Index::class)
            ->call('confirmDelete', $user->id)
            ->call('delete');

        $this->assertNotNull($user->fresh());
    }
}
