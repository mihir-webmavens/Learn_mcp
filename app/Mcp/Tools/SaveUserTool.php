<?php

namespace App\Mcp\Tools;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Mcp\Resources\UserManagerApp;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\RendersApp;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Ui\Enums\Visibility;

#[RendersApp(resource: UserManagerApp::class, visibility: [Visibility::App])]
#[Description('Create a user, or update an existing one when an ID is given. The password is optional when updating.')]
class SaveUserTool extends Tool
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $userId = $request->validate(['id' => ['nullable', 'integer']])['id'] ?? null;

        $user = $userId !== null ? User::find($userId) : null;

        if ($userId !== null && ! $user) {
            return Response::error('User not found.');
        }

        $rules = $this->profileRules($user?->id);

        $rules['password'] = $user !== null && blank($request->get('password'))
            ? ['nullable']
            : $this->passwordRules();

        $validated = $request->validate($rules);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        if ($user === null) {
            $user = User::create($validated);

            return Response::text("Created user #{$user->id}: {$user->name}.");
        }

        $user->update($validated);

        return Response::text("Updated user #{$user->id}: {$user->name}.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The ID of the user to update. Omit it to create a new user.'),
            'name' => $schema->string()->description('The user\'s full name.')->required(),
            'email' => $schema->string()->description('The user\'s email address.')->required(),
            'password' => $schema->string()->description('The new password. Required when creating a user.'),
            'password_confirmation' => $schema->string()->description('Must match the password.'),
        ];
    }
}
