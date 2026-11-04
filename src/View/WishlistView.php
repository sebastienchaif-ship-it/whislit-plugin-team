<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\View;

/** Data passed to the wishlist page as `wishlist` (contract with the front end). */
final class WishlistView
{
    /** @param list<WishlistItemView> $items */
    public function __construct(
        public readonly array $items,
        public readonly ?int $id = null,
        public readonly ?string $name = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->items;
    }
}
