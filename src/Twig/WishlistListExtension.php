<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Twig;

use PsSummit\SyliusWishlistPlugin\Context\WishlistContextInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class WishlistListExtension extends AbstractExtension
{
    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // The visitor's wishlists (oldest first, the first one is the default list), e.g. for the destination picker.
            new TwigFunction('ps_summit_wishlists', $this->getWishlists(...)),
        ];
    }

    /** @return list<WishlistInterface> */
    public function getWishlists(): array
    {
        return $this->wishlistContext->getWishlists();
    }
}
