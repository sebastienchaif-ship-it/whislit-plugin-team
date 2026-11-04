<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Mover;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use PsSummit\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Moves a product from one wishlist to another (add to the target, remove from the source). */
final class WishlistItemMover
{
    /** @param FactoryInterface<WishlistItemInterface> $wishlistItemFactory */
    public function __construct(
        #[Autowire(service: 'ps_summit_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        #[Autowire(service: 'ps_summit_wishlist.factory.wishlist_item')]
        private readonly FactoryInterface $wishlistItemFactory,
    ) {
    }

    /** @return bool false when the item is already in the target wishlist (nothing to do) */
    public function move(WishlistItemInterface $item, WishlistInterface $target): bool
    {
        $source = $item->getWishlist();
        $variant = $item->getVariant();
        if (null === $source || null === $variant || $source === $target) {
            return false;
        }

        // Target already has the product: no duplicate, it just leaves the source list.
        if (!$target->hasVariant($variant)) {
            // New item: moving the existing one would make Doctrine delete it (orphanRemoval).
            $newItem = $this->wishlistItemFactory->createNew();
            $newItem->setVariant($variant);
            $target->addItem($newItem);
            $this->wishlistRepository->add($target);
        }

        $source->removeItem($item);
        $this->wishlistRepository->add($source);

        return true;
    }
}
