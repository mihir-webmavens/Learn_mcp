<?php

use App\Mcp\Servers\UserServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/user', UserServer::class)->middleware(['throttle:mcp']);
