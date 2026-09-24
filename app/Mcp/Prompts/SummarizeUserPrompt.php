<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Generates a natural-language summary for a given user by ID.')]
class SummarizeUserPrompt extends Prompt
{
    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'userId' => 'required|integer',
        ]);

        $userId = $validated['userId'];

        return [
            Response::text(
                "You are a helpful assistant. Fetch the user with ID {$userId} "
                .'using the GetUser tool, then summarize their profile in 2-3 friendly sentences.'
            )->asAssistant(),

            Response::text("Please summarize user ID {$userId}."),
        ];
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'userId',
                description: 'The ID of the user to summarize.',
                required: true,
            ),
        ];
    }
}
