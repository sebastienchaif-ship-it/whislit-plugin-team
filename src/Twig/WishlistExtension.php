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
 * they use findWishlist(), so rendering a page never creates a wishlist. Looked up once per request.
 */
final class WishlistExtension extends AbstractExtension
{
    private bool $loaded = false;

    private ?WishlistInterface $wishlist = null;

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

    public function count(): int
    {
        return $this->wishlist()?->getItems()->count() ?? 0;
    }

    public function hasVariant(?ProductVariantInterface $variant): bool
    {
        if (null === $variant) {
            return false;
        }

        return $this->wishlist()?->hasVariant($variant) ?? false;
    }

    private function wishlist(): ?WishlistInterface
    {
        if (!$this->loaded) {
            $this->wishlist = $this->wishlistContext->findWishlist();
            $this->loaded = true;
        }

        return $this->wishlist;
    }
}
