<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Checker;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;

/**
 * Tells whether a wishlist variant can be bought right now in the given channel.
 * Used for the "available" flag on the wishlist page; the cart itself still runs
 * Sylius' own add-to-cart validation.
 */
final class WishlistVariantAvailabilityChecker
{
    public function __construct(
        private readonly AvailabilityCheckerInterface $availabilityChecker,
    ) {
    }

    public function isAvailable(ProductVariantInterface $variant, ChannelInterface $channel): bool
    {
        $product = $variant->getProduct();

        return null !== $product
            && $product->isEnabled()
            && $variant->isEnabled()
            && $product->hasChannel($channel)
            && null !== $variant->getChannelPricingForChannel($channel)
            && $this->availabilityChecker->isStockAvailable($variant);
    }
}
