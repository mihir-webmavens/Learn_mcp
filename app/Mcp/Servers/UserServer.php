<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\SummarizeUserPrompt;
use App\Mcp\Resources\UserListResource;
use App\Mcp\Resources\UserManagerApp;
use App\Mcp\Resources\UserResource;
use App\Mcp\Tools\DeleteUserTool;
use App\Mcp\Tools\GetUserTool;
use App\Mcp\Tools\ListUsersTool;
use App\Mcp\Tools\SaveUserTool;
use App\Mcp\Tools\SearchUsersTool;
use App\Mcp\Tools\ShowUserManagerTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('User Server')]
#[Version('0.0.1')]
#[Instructions('Read and manage the users of the application. Deleting a user is permanent and always requires two calls to the delete tool: preview first, get the human\'s explicit approval, then confirm with the returned token.')]
class UserServer extends Server
{
    protected array $tools = [
        GetUserTool::class,
        ListUsersTool::class,
        DeleteUserTool::class,
        ShowUserManagerTool::class,
        SearchUsersTool::class,
        SaveUserTool::class,
    ];

    protected array $resources = [
        UserListResource::class,
        UserResource::class,
        UserManagerApp::class,
    ];

    protected array $prompts = [
        SummarizeUserPrompt::class,
    ];
}
