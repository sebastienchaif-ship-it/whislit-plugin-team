<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Factory;

use PsSummit\SyliusWishlistPlugin\Context\WishlistContextInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Decorates the Sylius resource factory of wishlist items (ps_summit_wishlist.factory.wishlist_item).
 * Used by the "add" route through the ResourceController createAction (_sylius.factory).
 */
final class WishlistItemFactory implements WishlistItemFactoryInterface
{
    /**
     * @param FactoryInterface<WishlistItemInterface> $decoratedFactory
     * @param ProductVariantRepositoryInterface<ProductVariantInterface> $productVariantRepository
     */
    public function __construct(
        private readonly FactoryInterface $decoratedFactory,
        private readonly WishlistContextInterface $wishlistContext,
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function createNew(): WishlistItemInterface
    {
        return $this->decoratedFactory->createNew();
    }

    public function createForCurrentWishlistAndVariant(int|string $variantId): WishlistItemInterface
    {
        $variant = $this->productVariantRepository->find($variantId);
        $product = $variant?->getProduct();
        if (
            !$variant instanceof ProductVariantInterface
            || null === $product
            || !$variant->isEnabled()
            || !$product->isEnabled()
            || !$product->hasChannel($this->channelContext->getChannel())
        ) {
            throw new NotFoundHttpException('Product variant not found.');
        }

        $item = $this->createNew();
        $item->setVariant($variant);
        // Linked to the wishlist, but added to its collection only in WishlistItemListener::onPreCreate
        // (after the duplicate check).
        $item->setWishlist($this->wishlistContext->getOrCreateWishlist());

        return $item;
    }
}
