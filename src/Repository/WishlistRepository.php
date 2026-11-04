<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * @extends EntityRepository<WishlistInterface>
 */
class WishlistRepository extends EntityRepository implements WishlistRepositoryInterface
{
    public function findAllByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): array
    {
        return $this->getWishlists($this->createListQueryBuilder($channel)
            ->andWhere('o.customer = :customer')
            ->setParameter('customer', $customer));
    }

    public function findAllByTokenAndChannel(string $token, ChannelInterface $channel): array
    {
        return $this->getWishlists($this->createListQueryBuilder($channel)
            ->andWhere('o.token = :token')
            ->andWhere('o.customer IS NULL')
            ->setParameter('token', $token));
    }

    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?WishlistInterface
    {
        return $this->findAllByCustomerAndChannel($customer, $channel)[0] ?? null;
    }

    public function findOneByTokenAndChannel(string $token, ChannelInterface $channel): ?WishlistInterface
    {
        return $this->findAllByTokenAndChannel($token, $channel)[0] ?? null;
    }

    /** Wishlists of a channel, oldest first, with their items and variants fetch-joined. */
    private function createListQueryBuilder(ChannelInterface $channel): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->addSelect('item', 'variant')
            ->leftJoin('o.items', 'item')
            ->leftJoin('item.variant', 'variant')
            ->andWhere('o.channel = :channel')
            ->setParameter('channel', $channel)
            ->addOrderBy('o.createdAt', 'ASC')
            ->addOrderBy('o.id', 'ASC')
        ;
    }

    /** @return list<WishlistInterface> */
    private function getWishlists(QueryBuilder $queryBuilder): array
    {
        $result = $queryBuilder->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn (mixed $wishlist): bool => $wishlist instanceof WishlistInterface));
    }
}
