<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

/**
 * Contract agreed with the entity side (feat/back-entity).
 * A wishlist belongs either to a customer or to a guest token, always within one channel.
 */
interface WishlistInterface extends ResourceInterface
{
    public function getCustomer(): ?CustomerInterface;

    public function setCustomer(?CustomerInterface $customer): void;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    public function getToken(): ?string;

    public function setToken(?string $token): void;

    /** @return Collection<array-key, WishlistItemInterface> */
    public function getItems(): Collection;

    public function hasVariant(ProductVariantInterface $variant): bool;

    public function addItem(WishlistItemInterface $item): void;

    public function removeItem(WishlistItemInterface $item): void;
}
