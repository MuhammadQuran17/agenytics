<?php

namespace App\Services\Subscription;

use Illuminate\Container\Attributes\CurrentUser;
use App\Models\User;

class SubscriptionValidation
{
    public function __construct(
        #[CurrentUser] private readonly User $user
    ) {}

    /**
     * Validate if user has any available prompts from all sources
     * Checks: free prompts, purchased prompts (not expired), or active subscription
     **/
    public function hasAvailablePrompts(): bool
    {
        return $this->user->free_prompts > 0
            || ($this->user->purchased_prompts > 0 && $this->user->isPurchasedPromptsNotExpired())
            || $this->user->subscribed();
    }
}
