<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Controller;

use PsSummit\SyliusWishlistPlugin\Adder\WishlistItemsToCartAdder;
use PsSummit\SyliusWishlistPlugin\Context\WishlistContextInterface;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use PsSummit\SyliusWishlistPlugin\Mover\WishlistItemMover;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resource controller of ps_summit_wishlist.wishlist (sylius_resource classes.controller).
 * index / show / create / update / delete: standard ResourceController actions.
 * Extra actions: add selected items to the cart, move an item to another wishlist.
 * The wishlist is always resolved through ps_summit_wishlist.context.wishlist (visitor's own lists only).
 */
class WishlistController extends ResourceController
{
    public const ADD_TO_CART_CSRF_TOKEN_ID = 'ps_summit_wishlist_add_to_cart';

    public const MOVE_CSRF_TOKEN_ID = 'ps_summit_wishlist_move';

    public function addToCartAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, 'add_to_cart');
        $wishlist = $this->findOr404($configuration);
        \assert($wishlist instanceof WishlistInterface);

        $flashBag = $request->getSession()->getFlashBag();
        $redirect = $this->redirectHandler->redirectToReferer($configuration);

        if (!$this->isCsrfTokenValid(self::ADD_TO_CART_CSRF_TOKEN_ID, (string) $request->request->get('_csrf_token'))) {
            $flashBag->add('error', 'ps_summit_wishlist.invalid_csrf_token');

            return $redirect;
        }

        $adder = $this->get('ps_summit_wishlist.adder.wishlist_items_to_cart');
        \assert($adder instanceof WishlistItemsToCartAdder);
        $result = $adder->add($wishlist, $request->request->all('items'));

        if (0 === $result['selected']) {
            $flashBag->add('error', 'ps_summit_wishlist.no_item_selected');
        }
        if ($result['added'] > 0) {
            $flashBag->add('success', ['message' => 'ps_summit_wishlist.added_to_cart', 'parameters' => ['%count%' => $result['added']]]);
        }
        if ($result['skipped'] > 0) {
            $flashBag->add('error', ['message' => 'ps_summit_wishlist.not_added_to_cart', 'parameters' => ['%count%' => $result['skipped']]]);
        }

        return $redirect;
    }

    /** Target wishlist = the resource (targetWishlistId); the item must belong to one of the visitor's lists. */
    public function moveAction(Request $request, int|string $itemId): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, 'move');
        $target = $this->findOr404($configuration);
        \assert($target instanceof WishlistInterface);

        $flashBag = $request->getSession()->getFlashBag();
        $redirect = $this->redirectHandler->redirectToReferer($configuration);

        if (!$this->isCsrfTokenValid(self::MOVE_CSRF_TOKEN_ID, (string) $request->request->get('_csrf_token'))) {
            $flashBag->add('error', 'ps_summit_wishlist.invalid_csrf_token');

            return $redirect;
        }

        $context = $this->get('ps_summit_wishlist.context.wishlist');
        \assert($context instanceof WishlistContextInterface);
        $item = $context->findItem($itemId);
        if (null === $item) {
            throw new NotFoundHttpException('Wishlist item not found.');
        }

        $mover = $this->get('ps_summit_wishlist.mover.wishlist_item');
        \assert($mover instanceof WishlistItemMover);
        if ($mover->move($item, $target)) {
            $flashBag->add('success', ['message' => 'ps_summit_wishlist.moved_to_wishlist', 'parameters' => ['%name%' => $target->getName()]]);
        }

        return $redirect;
    }
}
