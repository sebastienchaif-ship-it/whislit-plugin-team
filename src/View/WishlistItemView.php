<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\View;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/** Data passed to the wishlist page for each item (contract with the front end). */
final class WishlistItemView
{
    public function __construct(
        public readonly int $id,
        public readonly ProductVariantInterface $variant,
        public readonly ProductInterface $product,
        public readonly string $name,
        public readonly ?string $variantName,
        public readonly ?string $image,
        public readonly ?int $price,
        public readonly ?int $originalPrice,
        public readonly bool $available,
    ) {
    }
}
