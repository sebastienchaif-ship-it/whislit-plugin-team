<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Resolver;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Redirects back to the page the shopper came from (_redirect field or same-host referer), or to the wishlist page. */
final class RedirectBackResolver
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function redirectBack(Request $request): RedirectResponse
    {
        // Explicit target sent by the form (relative path only, to avoid open redirects).
        $redirect = $request->request->get('_redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//') && !str_contains($redirect, '\\')) {
            return new RedirectResponse($redirect);
        }

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
