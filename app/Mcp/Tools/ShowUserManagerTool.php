<?php

namespace App\Mcp\Tools;

use App\Mcp\Resources\UserManagerApp;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\RendersApp;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[RendersApp(resource: UserManagerApp::class)]
#[Description('Open the interactive user manager so the human can search, create, edit, and delete users.')]
class ShowUserManagerTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        return Response::text('The user manager is open.');
    }
}
