<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

/**
 * A named wishlist. It belongs either to a logged-in customer (customer set)
 * or to a guest identified by a cookie token (token set), always within a channel.
 * A customer or a guest can have several wishlists; a variant appears at most once per wishlist.
 */
interface WishlistInterface extends ResourceInterface
{
    /** Name of the default wishlist; also given to the Stage 1 wishlists by the Stage 2 migration. */
    public const DEFAULT_NAME = 'My Favorites';

    public function getName(): ?string;

    /** Stores the name trimmed. */
    public function setName(?string $name): void;

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
