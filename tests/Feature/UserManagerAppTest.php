<?php

use App\Mcp\Resources\UserManagerApp;
use App\Mcp\Servers\UserServer;
use App\Mcp\Tools\SaveUserTool;
use App\Mcp\Tools\SearchUsersTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('renders the user manager app', function () {
    UserServer::resource(UserManagerApp::class)
        ->assertOk()
        ->assertSee(['id="app"', 'search-users-tool', 'save-user-tool', 'delete-user-tool']);
});

it('searches users by name or email', function () {
    User::factory()->create(['name' => 'Alice Smith', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Jones', 'email' => 'bob@example.com']);

    UserServer::tool(SearchUsersTool::class, ['search' => 'alice'])
        ->assertOk()
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Jones');
});

it('creates a user', function () {
    UserServer::tool(SaveUserTool::class, [
        'name' => 'New Person',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertOk()->assertSee('Created user');

    expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
});

it('updates a user and keeps the password when left blank', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    UserServer::tool(SaveUserTool::class, [
        'id' => $user->id,
        'name' => 'Renamed',
        'email' => $user->email,
    ])->assertOk()->assertSee('Updated user');

    expect($user->fresh())
        ->name->toBe('Renamed')
        ->password->toBe($originalPassword);
});

it('changes the password when a new one is given', function () {
    $user = User::factory()->create();

    UserServer::tool(SaveUserTool::class, [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    UserServer::tool(SaveUserTool::class, [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertHasErrors();

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

it('requires a password when creating a user', function () {
    UserServer::tool(SaveUserTool::class, [
        'name' => 'Someone',
        'email' => 'someone@example.com',
    ])->assertHasErrors();

    expect(User::where('email', 'someone@example.com')->exists())->toBeFalse();
});

it('returns an error when updating a missing user', function () {
    UserServer::tool(SaveUserTool::class, [
        'id' => 999,
        'name' => 'Ghost',
        'email' => 'ghost@example.com',
    ])->assertHasErrors(['User not found.']);
});
