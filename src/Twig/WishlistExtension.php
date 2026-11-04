<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Twig;

use PsSummit\SyliusWishlistPlugin\Context\WishlistContextInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Read-only access to the current visitor's wishlist from templates (header counter, filled heart).
 * Uses findWishlist(), so rendering a page never creates a wishlist. Looked up once per request.
 */
final class WishlistExtension extends AbstractExtension
{
    private bool $loaded = false;

    private ?WishlistInterface $wishlist = null;

    public function __construct(private readonly WishlistContextInterface $wishlistContext)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ps_summit_wishlist_count', $this->count(...)),
            new TwigFunction('ps_summit_wishlist_has_variant', $this->hasVariant(...)),
        ];
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
