<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Repository;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * @extends EntityRepository<WishlistInterface>
 */
class WishlistRepository extends EntityRepository implements WishlistRepositoryInterface
{
    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?WishlistInterface
    {
        return $this->createQueryBuilder('o')
            ->addSelect('item', 'variant')
            ->leftJoin('o.items', 'item')
            ->leftJoin('item.variant', 'variant')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.channel = :channel')
            ->setParameter('customer', $customer)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByTokenAndChannel(string $token, ChannelInterface $channel): ?WishlistInterface
    {
        return $this->createQueryBuilder('o')
            ->addSelect('item', 'variant')
            ->leftJoin('o.items', 'item')
            ->leftJoin('item.variant', 'variant')
            ->andWhere('o.token = :token')
            ->andWhere('o.customer IS NULL')
            ->andWhere('o.channel = :channel')
            ->setParameter('token', $token)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
