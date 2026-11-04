<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Repository;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * Lookups return the wishlists oldest first, with their items and variants fetch-joined.
 *
 * @extends RepositoryInterface<WishlistInterface>
 */
interface WishlistRepositoryInterface extends RepositoryInterface
{
    /** @return list<WishlistInterface> */
    public function findAllByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): array;

    /**
     * Guest wishlists only (no customer).
     *
     * @return list<WishlistInterface>
     */
    public function findAllByTokenAndChannel(string $token, ChannelInterface $channel): array;

    /** Oldest wishlist of the customer in the channel (the default one), or null. */
    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?WishlistInterface;

    /** Oldest guest wishlist for the token in the channel (the default one), or null. */
    public function findOneByTokenAndChannel(string $token, ChannelInterface $channel): ?WishlistInterface;
}
