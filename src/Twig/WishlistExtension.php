<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Twig;

use PsSummit\SyliusWishlistPlugin\Context\WishlistContextInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use PsSummit\SyliusWishlistPlugin\View\WishlistView;
use PsSummit\SyliusWishlistPlugin\View\WishlistViewFactory;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Template helpers. ps_summit_wishlist_count / ps_summit_wishlist_has_variant are read-only:
 * they use getWishlists() (all the visitor's lists), so rendering a page never creates a wishlist. Looked up once per request.
 */
final class WishlistExtension extends AbstractExtension
{
    /** @var list<WishlistInterface>|null */
    private ?array $wishlists = null;

    public function __construct(
        private readonly WishlistViewFactory $wishlistViewFactory,
        private readonly ChannelContextInterface $channelContext,
        private readonly WishlistContextInterface $wishlistContext,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // Display data of the wishlist resource: items with name, image, price, available.
            new TwigFunction('ps_summit_wishlist_view', $this->createView(...)),
            new TwigFunction('ps_summit_wishlist_count', $this->count(...)),
            new TwigFunction('ps_summit_wishlist_has_variant', $this->hasVariant(...)),
        ];
    }

    public function createView(?WishlistInterface $wishlist): WishlistView
    {
        $channel = $this->channelContext->getChannel();
        \assert($channel instanceof ChannelInterface);

        return $this->wishlistViewFactory->create($wishlist, $channel);
    }

    /** Items of all the visitor's wishlists (header counter). */
    public function count(): int
    {
        $count = 0;
        foreach ($this->wishlists() as $wishlist) {
            $count += $wishlist->getItems()->count();
        }

        return $count;
    }

    /** True when the variant is in at least one of the visitor's wishlists (filled heart). */
    public function hasVariant(?ProductVariantInterface $variant): bool
    {
        if (null === $variant) {
            return false;
        }

        foreach ($this->wishlists() as $wishlist) {
            if ($wishlist->hasVariant($variant)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<WishlistInterface> */
    private function wishlists(): array
    {
        if (null === $this->wishlists) {
            $this->wishlists = $this->wishlistContext->getWishlists();
        }

        return $this->wishlists;
    }
}
