<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Controller\Shop;

use PsSummit\SyliusWishlistPlugin\Context\WishlistContextInterface;
use PsSummit\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\Exception\InvalidCsrfTokenException;

#[AsController]
final class RemoveFromWishlistAction
{
    public const CSRF_TOKEN_ID = 'ps_summit_wishlist_remove';

    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
        #[Autowire(service: 'ps_summit_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RedirectBackResolver $redirectBackResolver,
    ) {
    }

    public function __invoke(Request $request, int $itemId): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, (string) $request->request->get('_csrf_token')))) {
            throw new InvalidCsrfTokenException();
        }

        // Look the item up inside the current wishlist only: nobody can remove items from someone else's wishlist.
        $wishlist = $this->wishlistContext->findWishlist();
        $item = null;
        foreach ($wishlist?->getItems() ?? [] as $wishlistItem) {
            if ($wishlistItem->getId() === $itemId) {
                $item = $wishlistItem;

                break;
            }
        }

        if (null === $wishlist || null === $item) {
            throw new NotFoundHttpException('Wishlist item not found.');
        }

        $wishlist->removeItem($item);
        $this->wishlistRepository->add($wishlist);

        $request->getSession()->getFlashBag()->add('success', 'ps_summit_wishlist.removed_from_wishlist');

        return $this->redirectBackResolver->redirectBack($request);
    }
}
