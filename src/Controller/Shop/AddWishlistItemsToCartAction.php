<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Controller\Shop;

use PsSummit\SyliusWishlistPlugin\Checker\WishlistVariantAvailabilityChecker;
use PsSummit\SyliusWishlistPlugin\Context\WishlistContextInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Sylius\Bundle\OrderBundle\Adder\CartItemAdderInterface;
use Sylius\Bundle\OrderBundle\Factory\AddToCartCommandFactoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\Exception\InvalidCsrfTokenException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Adds one or more wishlist items (POST items[]) to the native Sylius cart, quantity 1 each.
 * Items stay in the wishlist. Sylius' own add-to-cart validation (variant enabled, stock) is applied.
 */
#[AsController]
final class AddWishlistItemsToCartAction
{
    public const CSRF_TOKEN_ID = 'ps_summit_wishlist_add_to_cart';

    /** @param CartItemFactoryInterface<OrderItemInterface> $cartItemFactory */
    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
        private readonly CartContextInterface $cartContext,
        #[Autowire(service: 'sylius.factory.order_item')]
        private readonly CartItemFactoryInterface $cartItemFactory,
        private readonly OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private readonly AddToCartCommandFactoryInterface $addToCartCommandFactory,
        private readonly CartItemAdderInterface $cartItemAdder,
        private readonly ValidatorInterface $validator,
        private readonly WishlistVariantAvailabilityChecker $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RedirectBackResolver $redirectBackResolver,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, (string) $request->request->get('_csrf_token')))) {
            throw new InvalidCsrfTokenException();
        }

        $flashBag = $request->getSession()->getFlashBag();
        $selectedIds = array_map('intval', $request->request->all('items'));
        $items = $this->findSelectedItems($selectedIds);

        if ([] === $items) {
            $flashBag->add('error', 'ps_summit_wishlist.no_item_selected');

            return $this->redirectBackResolver->redirectToWishlist();
        }

        $channel = $this->channelContext->getChannel();
        \assert($channel instanceof ChannelInterface);
        $cart = $this->cartContext->getCart();
        \assert($cart instanceof OrderInterface);

        $added = 0;
        $skipped = 0;
        foreach ($items as $item) {
            if ($this->addToCart($item, $cart, $channel)) {
                ++$added;
            } else {
                ++$skipped;
            }
        }

        if ($added > 0) {
            $flashBag->add('success', ['message' => 'ps_summit_wishlist.added_to_cart', 'parameters' => ['%count%' => $added]]);
        }
        if ($skipped > 0) {
            $flashBag->add('error', ['message' => 'ps_summit_wishlist.not_added_to_cart', 'parameters' => ['%count%' => $skipped]]);
        }

        return $this->redirectBackResolver->redirectToWishlist();
    }

    /**
     * Only items of the current wishlist are considered: ids of other wishlists are silently ignored.
     *
     * @param list<int> $selectedIds
     *
     * @return list<WishlistItemInterface>
     */
    private function findSelectedItems(array $selectedIds): array
    {
        $wishlist = $this->wishlistContext->findWishlist();
        if (null === $wishlist || [] === $selectedIds) {
            return [];
        }

        $items = [];
        foreach ($wishlist->getItems() as $item) {
            if (in_array($item->getId(), $selectedIds, true)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function addToCart(WishlistItemInterface $item, OrderInterface $cart, ChannelInterface $channel): bool
    {
        $variant = $item->getVariant();
        if (null === $variant || !$this->availabilityChecker->isAvailable($variant, $channel)) {
            return false;
        }

        $cartItem = $this->cartItemFactory->createForCart($cart);
        $cartItem->setVariant($variant);
        $this->orderItemQuantityModifier->modify($cartItem, 1);

        $command = $this->addToCartCommandFactory->createWithCartAndCartItem($cart, $cartItem);
        if (count($this->validator->validate($command, null, ['sylius'])) > 0) {
            return false;
        }

        $this->cartItemAdder->add($command);

        return true;
    }
}
