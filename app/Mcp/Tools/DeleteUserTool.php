<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
#[Description('Delete a user by their ID. Deleting requires two calls: call without a confirmation token to preview the user, show that preview to the human and wait for their explicit approval, then call again with the returned confirmation token.')]
class DeleteUserTool extends Tool
{
    /**
     * The number of minutes a confirmation token stays valid.
     */
    protected int $confirmationTtlMinutes = 5;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'confirmation_token' => ['nullable', 'string'],
        ], [
            'id.required' => 'You must specify the ID of the user to delete.',
        ]);

        $user = User::find($validated['id']);

        if (! $user) {
            return Response::error('User not found.');
        }

        if (blank($validated['confirmation_token'] ?? null)) {
            return $this->requestConfirmation($user);
        }

        return $this->deleteConfirmed($user, $validated['confirmation_token']);
    }

    /**
     * Issue a confirmation token and ask the human to approve the deletion.
     */
    protected function requestConfirmation(User $user): Response
    {
        $token = Str::random(8);

        Cache::put(
            $this->confirmationKey($user),
            $token,
            now()->addMinutes($this->confirmationTtlMinutes),
        );

        return Response::text(implode("\n", [
            'CONFIRMATION REQUIRED. Nothing has been deleted yet.',
            "About to permanently delete user #{$user->id}: {$user->name} <{$user->email}>.",
            "Ask the human to confirm this exact user, then call this tool again with confirmation_token \"{$token}\".",
            "The token expires in {$this->confirmationTtlMinutes} minutes. Never call again with the token unless the human has approved.",
        ]));
    }

    /**
     * Delete the user once a valid confirmation token is provided.
     */
    protected function deleteConfirmed(User $user, string $token): Response
    {
        $key = $this->confirmationKey($user);
        $pendingToken = Cache::get($key);

        if (! is_string($pendingToken) || ! hash_equals($pendingToken, $token)) {
            return Response::error(
                'That confirmation token is invalid, expired, or belongs to a different user. Call this tool again without a token to restart the confirmation flow.',
            );
        }

        Cache::forget($key);

        $name = $user->name;

        $user->delete();

        return Response::text("Deleted user #{$user->id}: {$name}.");
    }

    /**
     * Get the cache key holding the pending confirmation token for the given user.
     */
    protected function confirmationKey(User $user): string
    {
        return "mcp:delete-user:confirmation:{$user->id}";
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The ID of the user to delete.')
                ->required(),
            'confirmation_token' => $schema->string()
                ->description('The token returned by the first call. Only send it after the human has explicitly approved deleting this specific user.'),
        ];
    }
}
