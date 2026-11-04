<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Controller\Shop;

use PsSummit\SyliusWishlistPlugin\Context\WishlistContextInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use PsSummit\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\Exception\InvalidCsrfTokenException;

#[AsController]
final class AddToWishlistAction
{
    public const CSRF_TOKEN_ID = 'ps_summit_wishlist_add';

    /**
     * @param ProductVariantRepositoryInterface<ProductVariantInterface> $productVariantRepository
     * @param FactoryInterface<WishlistItemInterface> $wishlistItemFactory
     */
    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
        #[Autowire(service: 'ps_summit_wishlist.repository.wishlist')]
        private readonly WishlistRepositoryInterface $wishlistRepository,
        #[Autowire(service: 'ps_summit_wishlist.factory.wishlist_item')]
        private readonly FactoryInterface $wishlistItemFactory,
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
        private readonly ChannelContextInterface $channelContext,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RedirectBackResolver $redirectBackResolver,
    ) {
    }

    public function __invoke(Request $request, int $variantId): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, (string) $request->request->get('_csrf_token')))) {
            throw new InvalidCsrfTokenException();
        }

        $variant = $this->productVariantRepository->find($variantId);
        $product = $variant?->getProduct();
        if (
            !$variant instanceof ProductVariantInterface
            || null === $product
            || !$variant->isEnabled()
            || !$product->isEnabled()
            || !$product->hasChannel($this->channelContext->getChannel())
        ) {
            throw new NotFoundHttpException('Product variant not found.');
        }

        $wishlist = $this->wishlistContext->getOrCreateWishlist();
        $flashBag = $request->getSession()->getFlashBag();

        if ($wishlist->hasVariant($variant)) {
            $flashBag->add('info', 'ps_summit_wishlist.already_in_wishlist');

            return $this->redirectBackResolver->redirectBack($request);
        }

        $item = $this->wishlistItemFactory->createNew();
        $item->setVariant($variant);
        $wishlist->addItem($item);
        $this->wishlistRepository->add($wishlist);

        $flashBag->add('success', 'ps_summit_wishlist.added_to_wishlist');

        return $this->redirectBackResolver->redirectBack($request);
    }
}
