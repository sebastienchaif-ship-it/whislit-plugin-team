<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Context;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;

/**
 * Wishlists of the current visitor (logged-in customer, or guest identified by the cookie token),
 * in the current channel. Every lookup by id is limited to the visitor's own wishlists.
 */
interface WishlistContextInterface
{
    public const DEFAULT_WISHLIST_NAME = 'My Favorites';

    /** @return list<WishlistInterface> oldest first */
    public function getWishlists(): array;

    /** Default wishlist (the oldest one), or null. Never creates anything. */
    public function findWishlist(): ?WishlistInterface;

    /** Default wishlist, creating "My Favorites" (not yet persisted) when the visitor has none. */
    public function getOrCreateWishlist(): WishlistInterface;

    /** Default wishlist, or an empty one neither persisted nor linked to a cookie (for display only). */
    public function getWishlistForDisplay(): WishlistInterface;

    /** The visitor's wishlist with this id, or null (another visitor's wishlist is never returned). */
    public function findWishlistById(int|string|null $wishlistId): ?WishlistInterface;

    /** With an id: the visitor's wishlist or null. Without: the default wishlist (created if missing). */
    public function getOrCreateWishlistById(int|string|null $wishlistId): ?WishlistInterface;

    /** With an id: the visitor's wishlist or null. Without: getWishlistForDisplay(). */
    public function getWishlistByIdForDisplay(int|string|null $wishlistId): ?WishlistInterface;

    /** New, not yet persisted wishlist owned by the visitor (customer, or guest token). */
    public function createWishlist(): WishlistInterface;

    /** Item with this id in one of the visitor's wishlists, or null. */
    public function findItem(int|string $itemId): ?WishlistItemInterface;
}
