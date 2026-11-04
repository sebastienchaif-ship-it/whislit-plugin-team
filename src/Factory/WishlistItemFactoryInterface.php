<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Factory;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Sylius\Resource\Factory\FactoryInterface;

/** @extends FactoryInterface<WishlistItemInterface> */
interface WishlistItemFactoryInterface extends FactoryInterface
{
    /**
     * New item for the given variant, linked to one of the visitor's wishlists:
     * the one with $wishlistId, or the default wishlist (created if missing) when no id is given.
     */
    public function createForCurrentWishlistAndVariant(int|string $variantId, int|string|null $wishlistId = null): WishlistItemInterface;
}
