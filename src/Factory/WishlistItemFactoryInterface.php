<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Factory;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Sylius\Resource\Factory\FactoryInterface;

/** @extends FactoryInterface<WishlistItemInterface> */
interface WishlistItemFactoryInterface extends FactoryInterface
{
    /** New item for the given variant, linked to the current shopper's wishlist (created if missing). */
    public function createForCurrentWishlistAndVariant(int|string $variantId): WishlistItemInterface;
}
