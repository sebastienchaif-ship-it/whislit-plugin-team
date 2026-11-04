<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Context;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;

interface WishlistContextInterface
{
    /** Current wishlist (logged-in customer or guest cookie), or null if none exists yet. Never creates anything. */
    public function findWishlist(): ?WishlistInterface;

    /** Current wishlist, creating a new (not yet persisted) one when missing. */
    public function getOrCreateWishlist(): WishlistInterface;

    /** Current wishlist, or an empty one that is neither persisted nor linked to a cookie (for display only). */
    public function getWishlistForDisplay(): WishlistInterface;

    /** Item of the current wishlist with this id; null for items of other wishlists. */
    public function findItem(int|string $itemId): ?WishlistItemInterface;
}
