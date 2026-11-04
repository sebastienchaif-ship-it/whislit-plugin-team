<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Context;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;

interface WishlistContextInterface
{
    /** Current wishlist (logged-in customer or guest cookie), or null if none exists yet. Never creates anything. */
    public function findWishlist(): ?WishlistInterface;

    /** Current wishlist, creating a new (not yet persisted) one when missing. */
    public function getOrCreateWishlist(): WishlistInterface;
}
