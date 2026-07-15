<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use App\Services\Auth\AuthServiceInterface;
use App\Services\Messaging\ConversationService;
use App\Services\Messaging\ConversationServiceInterface;
use App\Services\Messaging\MessageService;
use App\Services\Messaging\MessageServiceInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Reference: architecture.md §3.3/§3.4 (Business Layer)
 */
class BusinessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(ConversationServiceInterface::class, ConversationService::class);
        $this->app->bind(MessageServiceInterface::class, MessageService::class);
    }
}
