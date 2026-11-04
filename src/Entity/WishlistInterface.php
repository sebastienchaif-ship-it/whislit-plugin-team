<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

/**
 * A wishlist belongs either to a logged-in customer (customer set)
 * or to a guest identified by a cookie token (token set), always within a channel.
 */
interface WishlistInterface extends ResourceInterface
{
    public function getToken(): ?string;

    public function setToken(?string $token): void;

    public function getCustomer(): ?CustomerInterface;

    public function setCustomer(?CustomerInterface $customer): void;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    /** @return Collection<int, WishlistItemInterface> */
    public function getItems(): Collection;

    public function hasItems(): bool;

    public function hasItem(WishlistItemInterface $item): bool;

    /** Adds the item unless the same variant is already in the wishlist. */
    public function addItem(WishlistItemInterface $item): void;

    public function removeItem(WishlistItemInterface $item): void;

    public function hasVariant(ProductVariantInterface $variant): bool;

    public function getItemByVariant(ProductVariantInterface $variant): ?WishlistItemInterface;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): ?\DateTimeImmutable;

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void;
}
