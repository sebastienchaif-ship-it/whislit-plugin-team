<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\View;

use PsSummit\SyliusWishlistPlugin\Checker\WishlistVariantAvailabilityChecker;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Exception\MissingChannelConfigurationException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class WishlistViewFactory
{
    public function __construct(
        private readonly ProductVariantPricesCalculatorInterface $pricesCalculator,
        private readonly WishlistVariantAvailabilityChecker $availabilityChecker,
    ) {
    }

    public function create(?WishlistInterface $wishlist, ChannelInterface $channel): WishlistView
    {
        if (null === $wishlist) {
            return new WishlistView([]);
        }

        $items = [];
        foreach ($wishlist->getItems() as $item) {
            $variant = $item->getVariant();
            $product = $variant?->getProduct();
            if (!$variant instanceof ProductVariantInterface || !$product instanceof ProductInterface) {
                continue;
            }

            [$price, $originalPrice] = $this->getPrices($variant, $channel);

            $items[] = new WishlistItemView(
                id: (int) $item->getId(),
                variant: $variant,
                product: $product,
                name: (string) $product->getName(),
                variantName: $product->hasOptions() ? $variant->getName() : null,
                image: $this->getImagePath($variant, $product),
                price: $price,
                originalPrice: $originalPrice,
                available: null !== $price && $this->availabilityChecker->isAvailable($variant, $channel),
            );
        }

        return new WishlistView($items, null !== $wishlist->getId() ? (int) $wishlist->getId() : null, $wishlist->getName());
    }

    /** @return array{0: ?int, 1: ?int} */
    private function getPrices(ProductVariantInterface $variant, ChannelInterface $channel): array
    {
        try {
            return [
                $this->pricesCalculator->calculate($variant, ['channel' => $channel]),
                $this->pricesCalculator->calculateOriginal($variant, ['channel' => $channel]),
            ];
        } catch (MissingChannelConfigurationException) {
            // Variant not priced in this channel any more: still shown, but not purchasable.
            return [null, null];
        }
    }

    private function getImagePath(ProductVariantInterface $variant, ProductInterface $product): ?string
    {
        $image = $variant->getImages()->first() ?: $product->getImages()->first();

        return false !== $image ? $image->getPath() : null;
    }
}
