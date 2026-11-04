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
 * On login, hands the guest's wishlists (same channel) over to the customer:
 * - a guest list whose name matches one of the customer's lists (case-insensitive) is merged into it,
 *   skipping products already there, then deleted;
 * - any other guest list simply becomes a customer list.
 */
final class GuestWishlistMerger
{
    /** @param FactoryInterface<WishlistItemInterface> $wishlistItemFactory */
    public function __construct(
        #[Autowire(service: 'ps_summit_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        #[Autowire(service: 'ps_summit_wishlist.factory.wishlist_item')]
        private readonly FactoryInterface $wishlistItemFactory,
    ) {
    }

    /** @return int number of products brought over from the guest wishlists */
    public function merge(string $guestToken, CustomerInterface $customer, ChannelInterface $channel): int
    {
        // Guest lists only (no customer), so a token can never steal a customer wishlist.
        $guestWishlists = $this->wishlistRepository->findAllByTokenAndChannel($guestToken, $channel);
        if ([] === $guestWishlists) {
            return 0;
        }

        $customerWishlists = [];
        foreach ($this->wishlistRepository->findAllByCustomerAndChannel($customer, $channel) as $wishlist) {
            $customerWishlists[$this->normalize($wishlist->getName())] ??= $wishlist;
        }

        $count = 0;
        foreach ($guestWishlists as $guestWishlist) {
            $target = $customerWishlists[$this->normalize($guestWishlist->getName())] ?? null;

            if (null === $target) {
                // No list with that name: the guest list becomes the customer's.
                $count += $guestWishlist->getItems()->count();
                $guestWishlist->setCustomer($customer);
                $guestWishlist->setToken(null);
                $this->wishlistRepository->add($guestWishlist);
                $customerWishlists[$this->normalize($guestWishlist->getName())] = $guestWishlist;

                continue;
            }

            $count += $this->mergeInto($guestWishlist, $target);
            $this->wishlistRepository->remove($guestWishlist);
        }

        return $count;
    }

    private function mergeInto(WishlistInterface $source, WishlistInterface $target): int
    {
        $added = 0;
        foreach ($source->getItems() as $sourceItem) {
            $variant = $sourceItem->getVariant();
            if (null === $variant || $target->hasVariant($variant)) {
                continue;
            }

            // New items: moving the guest ones would make Doctrine delete them (orphanRemoval).
            $item = $this->wishlistItemFactory->createNew();
            $item->setVariant($variant);
            $target->addItem($item);
            ++$added;
        }

        if ($added > 0) {
            $this->wishlistRepository->add($target);
        }

        return $added;
    }

    private function normalize(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }
}
