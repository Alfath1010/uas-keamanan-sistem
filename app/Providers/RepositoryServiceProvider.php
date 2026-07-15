<?php

namespace App\Providers;

use App\Repositories\ALSSessionRepository;
use App\Repositories\Contracts\ALSSessionRepositoryInterface;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\PublicKeyRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\ConversationRepository;
use App\Repositories\MessageRepository;
use App\Repositories\PublicKeyRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Binds repository contracts to their Eloquent implementations, per
 * the Persistence Layer described in architecture.md §3.3/§3.4.
 *
 * Business/Security layer services SHALL depend on the *Interface
 * contracts, not the concrete classes, so persistence can be swapped
 * without touching business logic (NFR-003).
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ConversationRepositoryInterface::class, ConversationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);
        $this->app->bind(PublicKeyRepositoryInterface::class, PublicKeyRepository::class);
        $this->app->bind(ALSSessionRepositoryInterface::class, ALSSessionRepository::class);
    }
}
