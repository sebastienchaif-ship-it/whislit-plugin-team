<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\EventSubscriber;

use PsSummit\SyliusWishlistPlugin\Context\WishlistContext;
use PsSummit\SyliusWishlistPlugin\Merger\GuestWishlistMerger;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/** On shop login, merges the guest wishlist (cookie) into the customer's wishlist and drops the cookie. */
final class MergeGuestWishlistOnLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly GuestWishlistMerger $merger,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLoginSuccess'];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof ShopUserInterface) {
            return;
        }

        $request = $event->getRequest();
        $token = $request->cookies->get(WishlistContext::COOKIE_NAME);
        $customer = $user->getCustomer();
        if (!is_string($token) || '' === $token || !$customer instanceof CustomerInterface) {
            return;
        }

        $added = $this->merger->merge($token, $customer, $this->channelContext->getChannel());

        // Guest wishlist is gone: forget its token (also when logging out later, a fresh one is created on demand).
        $request->cookies->remove(WishlistContext::COOKIE_NAME);
        $event->getResponse()?->headers->clearCookie(WishlistContext::COOKIE_NAME, '/', null, false, true, 'lax');

        $session = $request->hasSession() ? $request->getSession() : null;
        if ($added > 0 && $session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('info', ['message' => 'ps_summit_wishlist.guest_wishlist_merged', 'parameters' => ['%count%' => $added]]);
        }
    }
}
