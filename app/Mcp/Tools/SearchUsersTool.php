<?php

namespace App\Mcp\Tools;

use App\Mcp\Resources\UserManagerApp;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\RendersApp;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Ui\Enums\Visibility;

#[IsReadOnly]
#[RendersApp(resource: UserManagerApp::class, visibility: [Visibility::App])]
#[Description('Search users by name or email, one page at a time.')]
class SearchUsersTool extends Tool
{
    /**
     * The number of users returned per page.
     */
    protected int $perPage = 10;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim($validated['search'] ?? '');

        $users = User::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->latest()
            ->paginate($this->perPage, page: $validated['page'] ?? 1);

        return Response::json([
            'users' => $users->items(),
            'page' => $users->currentPage(),
            'lastPage' => $users->lastPage(),
            'total' => $users->total(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Filter users whose name or email contains this text.'),
            'page' => $schema->integer()->description('The page number to fetch, starting at 1.'),
        ];
    }
}
