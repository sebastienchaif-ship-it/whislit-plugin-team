<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Merger;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use PsSummit\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Moves the products of a guest wishlist into the customer's wishlist (same channel),
 * skipping variants the customer already saved, then deletes the guest wishlist.
 */
final class GuestWishlistMerger
{
    /**
     * @param FactoryInterface<WishlistInterface> $wishlistFactory
     * @param FactoryInterface<WishlistItemInterface> $wishlistItemFactory
     */
    public function __construct(
        #[Autowire(service: 'ps_summit_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        #[Autowire(service: 'ps_summit_wishlist.factory.wishlist')]
        private readonly FactoryInterface $wishlistFactory,
        #[Autowire(service: 'ps_summit_wishlist.factory.wishlist_item')]
        private readonly FactoryInterface $wishlistItemFactory,
    ) {
    }

    /** @return int number of products added to the customer's wishlist */
    public function merge(string $guestToken, CustomerInterface $customer, ChannelInterface $channel): int
    {
        // Only finds guest wishlists (no customer), so a token can never steal a customer wishlist.
        $guestWishlist = $this->wishlistRepository->findOneByTokenAndChannel($guestToken, $channel);
        if (null === $guestWishlist) {
            return 0;
        }

        $customerWishlist = $this->wishlistRepository->findOneByCustomerAndChannel($customer, $channel);
        if (null === $customerWishlist) {
            $customerWishlist = $this->wishlistFactory->createNew();
            $customerWishlist->setCustomer($customer);
            $customerWishlist->setChannel($channel);
        }

        $added = 0;
        foreach ($guestWishlist->getItems() as $guestItem) {
            $variant = $guestItem->getVariant();
            if (null === $variant || $customerWishlist->hasVariant($variant)) {
                continue;
            }

            // New items: moving the guest ones would make Doctrine delete them (orphanRemoval).
            $item = $this->wishlistItemFactory->createNew();
            $item->setVariant($variant);
            $customerWishlist->addItem($item);
            ++$added;
        }

        if ($added > 0 || null === $customerWishlist->getId()) {
            $this->wishlistRepository->add($customerWishlist);
        }
        $this->wishlistRepository->remove($guestWishlist);

        return $added;
    }
}
