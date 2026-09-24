<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Find a user by their ID')]
class GetUserTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = User::find($request->get('id'));

        if (! $user) {
            return Response::error('User not found');
        }

        return Response::text(
            json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
        );
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The Id of the user')->required(),
        ];
    }
}
