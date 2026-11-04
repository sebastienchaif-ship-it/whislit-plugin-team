<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Context;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use PsSummit\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the wishlist of the current shopper:
 * - logged-in customer: wishlist linked to the customer (persists across logins);
 * - guest: wishlist linked to a random token stored in a cookie (survives page refreshes).
 */
final class WishlistContext implements WishlistContextInterface
{
    public const COOKIE_NAME = 'ps_summit_wishlist_token';

    /** Request attribute read by WishlistTokenCookieSubscriber to write the cookie. */
    public const NEW_TOKEN_ATTRIBUTE = '_ps_summit_wishlist_new_token';

    /** @param FactoryInterface<WishlistInterface> $wishlistFactory */
    public function __construct(
        #[Autowire(service: 'ps_summit_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        #[Autowire(service: 'ps_summit_wishlist.factory.wishlist')]
        private readonly FactoryInterface $wishlistFactory,
        private readonly CustomerContextInterface $customerContext,
        private readonly ChannelContextInterface $channelContext,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function findWishlist(): ?WishlistInterface
    {
        $channel = $this->channelContext->getChannel();
        $customer = $this->getCustomer();

        if (null !== $customer) {
            return $this->wishlistRepository->findOneByCustomerAndChannel($customer, $channel);
        }

        $token = $this->getGuestToken();
        if (null === $token) {
            return null;
        }

        return $this->wishlistRepository->findOneByTokenAndChannel($token, $channel);
    }

    public function getOrCreateWishlist(): WishlistInterface
    {
        $wishlist = $this->findWishlist();
        if (null !== $wishlist) {
            return $wishlist;
        }

        $wishlist = $this->wishlistFactory->createNew();
        $wishlist->setChannel($this->channelContext->getChannel());

        $customer = $this->getCustomer();
        if (null !== $customer) {
            $wishlist->setCustomer($customer);

            return $wishlist;
        }

        $token = bin2hex(random_bytes(32));
        $wishlist->setToken($token);
        $this->requestStack->getMainRequest()?->attributes->set(self::NEW_TOKEN_ATTRIBUTE, $token);

        return $wishlist;
    }

    private function getCustomer(): ?CustomerInterface
    {
        $customer = $this->customerContext->getCustomer();

        // A customer without a user is a guest who checked out with an email: treat as guest.
        if (!$customer instanceof CustomerInterface || null === $customer->getUser()) {
            return null;
        }

        return $customer;
    }

    private function getGuestToken(): ?string
    {
        $token = $this->requestStack->getMainRequest()?->cookies->get(self::COOKIE_NAME);

        return is_string($token) && '' !== $token ? $token : null;
    }
}
