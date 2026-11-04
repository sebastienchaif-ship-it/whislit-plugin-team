<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Entity;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

interface WishlistItemInterface extends ResourceInterface
{
    public function getWishlist(): ?WishlistInterface;

    public function setWishlist(?WishlistInterface $wishlist): void;

    public function getVariant(): ?ProductVariantInterface;

    public function setVariant(?ProductVariantInterface $variant): void;

    public function getCreatedAt(): \DateTimeImmutable;
}
