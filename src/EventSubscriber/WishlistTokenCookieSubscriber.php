<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\EventSubscriber;

use PsSummit\SyliusWishlistPlugin\Context\WishlistContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Writes the guest wishlist cookie when a new guest wishlist was created during the request. */
final class WishlistTokenCookieSubscriber implements EventSubscriberInterface
{
    private const COOKIE_LIFETIME = '+1 year';

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $token = $request->attributes->get(WishlistContext::NEW_TOKEN_ATTRIBUTE);
        if (!is_string($token)) {
            return;
        }

        $event->getResponse()->headers->setCookie(
            Cookie::create(WishlistContext::COOKIE_NAME, $token, new \DateTimeImmutable(self::COOKIE_LIFETIME))
                ->withSecure($request->isSecure())
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX),
        );
    }
}
