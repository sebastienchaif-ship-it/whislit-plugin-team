<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\EventListener;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use PsSummit\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use PsSummit\SyliusWishlistPlugin\Resolver\RedirectBackResolver;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

/** Resource events of ps_summit_wishlist.wishlist_item ("add to wishlist" through createAction). */
final class WishlistItemListener
{
    public function __construct(
        #[Autowire(service: 'ps_summit_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly RedirectBackResolver $redirectBackResolver,
        private readonly RequestStack $requestStack,
    ) {
    }

    /** Duplicate check, then the item joins its wishlist (a new guest/customer wishlist is saved first). */
    #[AsEventListener(event: 'ps_summit_wishlist.wishlist_item.pre_create')]
    public function onPreCreate(ResourceControllerEvent $event): void
    {
        $item = $event->getSubject();
        if (!$item instanceof WishlistItemInterface) {
            return;
        }

        $wishlist = $item->getWishlist();
        $variant = $item->getVariant();
        if (null === $wishlist || null === $variant) {
            return;
        }

        if ($wishlist->hasVariant($variant)) {
            $event->stop('ps_summit_wishlist.already_in_wishlist', ResourceControllerEvent::TYPE_INFO);
            $event->setResponse($this->redirectBackResolver->redirectBack($this->requestStack->getMainRequest()));

            return;
        }

        $wishlist->addItem($item);
        if (null === $wishlist->getId()) {
            $this->wishlistRepository->add($wishlist);
        }
    }

    /** createAction only reaches "initialize" on a POST when the form is invalid, i.e. a bad CSRF token. */
    #[AsEventListener(event: 'ps_summit_wishlist.wishlist_item.initialize_create')]
    public function onInitializeCreate(ResourceControllerEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request || !$request->isMethod('POST')) {
            return;
        }

        $request->getSession()->getFlashBag()->add('error', 'ps_summit_wishlist.invalid_csrf_token');
        $event->setResponse($this->redirectBackResolver->redirectBack($request));
    }
}
