<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Context;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use PsSummit\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the wishlists of the current visitor:
 * - logged-in customer: wishlists linked to the customer (persist across logins);
 * - guest: wishlists linked to a random token stored in a cookie (survive page refreshes),
 *   all the guest's wishlists share the same token.
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

    public function getWishlists(): array
    {
        $channel = $this->channelContext->getChannel();
        $customer = $this->getCustomer();

        if (null !== $customer) {
            return array_values($this->wishlistRepository->findAllByCustomerAndChannel($customer, $channel));
        }

        $token = $this->getGuestToken();
        if (null === $token) {
            return [];
        }

        return array_values($this->wishlistRepository->findAllByTokenAndChannel($token, $channel));
    }

    public function findWishlist(): ?WishlistInterface
    {
        return $this->getWishlists()[0] ?? null;
    }

    public function getOrCreateWishlist(): WishlistInterface
    {
        $wishlist = $this->findWishlist();
        if (null !== $wishlist) {
            return $wishlist;
        }

        $wishlist = $this->createWishlist();
        $wishlist->setName(self::DEFAULT_WISHLIST_NAME);

        return $wishlist;
    }

    public function getWishlistForDisplay(): WishlistInterface
    {
        $wishlist = $this->findWishlist();
        if (null !== $wishlist) {
            return $wishlist;
        }

        $wishlist = $this->wishlistFactory->createNew();
        $wishlist->setChannel($this->channelContext->getChannel());
        $wishlist->setName(self::DEFAULT_WISHLIST_NAME);

        return $wishlist;
    }

    public function findWishlistById(int|string|null $wishlistId): ?WishlistInterface
    {
        if (null === $wishlistId || '' === $wishlistId) {
            return null;
        }

        foreach ($this->getWishlists() as $wishlist) {
            if ((string) $wishlist->getId() === (string) $wishlistId) {
                return $wishlist;
            }
        }

        return null;
    }

    public function getOrCreateWishlistById(int|string|null $wishlistId): ?WishlistInterface
    {
        if (null === $wishlistId || '' === $wishlistId) {
            return $this->getOrCreateWishlist();
        }

        return $this->findWishlistById($wishlistId);
    }

    public function getWishlistByIdForDisplay(int|string|null $wishlistId): ?WishlistInterface
    {
        if (null === $wishlistId || '' === $wishlistId) {
            return $this->getWishlistForDisplay();
        }

        return $this->findWishlistById($wishlistId);
    }

    public function createWishlist(): WishlistInterface
    {
        $wishlist = $this->wishlistFactory->createNew();
        $wishlist->setChannel($this->channelContext->getChannel());

        $customer = $this->getCustomer();
        if (null !== $customer) {
            $wishlist->setCustomer($customer);

            return $wishlist;
        }

        $wishlist->setToken($this->getOrCreateGuestToken());

        return $wishlist;
    }

    public function findItem(int|string $itemId): ?WishlistItemInterface
    {
        foreach ($this->getWishlists() as $wishlist) {
            foreach ($wishlist->getItems() as $item) {
                if ((string) $item->getId() === (string) $itemId) {
                    return $item;
                }
            }
        }

        return null;
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
        $request = $this->requestStack->getMainRequest();
        $token = $request?->attributes->get(self::NEW_TOKEN_ATTRIBUTE) ?? $request?->cookies->get(self::COOKIE_NAME);

        return is_string($token) && '' !== $token ? $token : null;
    }

    private function getOrCreateGuestToken(): string
    {
        $token = $this->getGuestToken();
        if (null !== $token) {
            return $token;
        }

        $token = bin2hex(random_bytes(32));
        $this->requestStack->getMainRequest()?->attributes->set(self::NEW_TOKEN_ATTRIBUTE, $token);

        return $token;
    }
}
