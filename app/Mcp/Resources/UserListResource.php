<?php

namespace App\Mcp\Resources;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Description('Provides a JSON list of all users with basic details.')]
#[Uri('users://all')]
#[MimeType('application/json')]
class UserListResource extends Resource
{
    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $users = User::select('id', 'name', 'email')->get();

        return Response::text($users->toJson());
    }
}
