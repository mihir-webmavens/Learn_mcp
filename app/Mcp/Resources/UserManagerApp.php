<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\AppResource;
use Laravel\Mcp\Server\Attributes\AppMeta;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Ui\Enums\Library;

#[Description('An interactive user manager to search, create, edit, and delete users.')]
#[AppMeta(libraries: [Library::Tailwind])]
class UserManagerApp extends AppResource
{
    /**
     * Handle the app resource request.
     */
    public function handle(Request $request): Response
    {
        return Response::view('mcp.user-manager-app', [
            'title' => $this->title(),
        ]);
    }
}
