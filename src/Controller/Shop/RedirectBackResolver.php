<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Controller\Shop;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Redirects back to the page the shopper came from (same host only), or to the wishlist page. */
final class RedirectBackResolver
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function redirectBack(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer');
        if (is_string($referer) && str_starts_with($referer, $request->getSchemeAndHttpHost() . '/')) {
            return new RedirectResponse($referer);
        }

        return $this->redirectToWishlist();
    }

    public function redirectToWishlist(): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('ps_summit_wishlist_show'));
    }
}
