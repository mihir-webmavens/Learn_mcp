<?php

namespace App\Mcp\Resources;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Description('Get full details of a single user by ID.')]
#[MimeType('application/json')]
class UserResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('users://{userId}');
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $userId = $request->get('userId');

        $user = User::findOrFail($userId);

        return Response::text($user->toJson());
    }
}
