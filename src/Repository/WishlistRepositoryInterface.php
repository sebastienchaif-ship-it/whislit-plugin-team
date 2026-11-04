<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Repository;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<WishlistInterface>
 */
interface WishlistRepositoryInterface extends RepositoryInterface
{
    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?WishlistInterface;

    public function findOneByTokenAndChannel(string $token, ChannelInterface $channel): ?WishlistInterface;
}
