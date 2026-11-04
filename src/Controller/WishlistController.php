<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Controller;

use PsSummit\SyliusWishlistPlugin\Adder\WishlistItemsToCartAdder;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resource controller of ps_summit_wishlist.wishlist (sylius_resource classes.controller).
 * show: standard showAction. Extra action: add selected wishlist items to the cart.
 */
class WishlistController extends ResourceController
{
    public const ADD_TO_CART_CSRF_TOKEN_ID = 'ps_summit_wishlist_add_to_cart';

    public function addToCartAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, 'add_to_cart');
        $wishlist = $this->findOr404($configuration);
        \assert($wishlist instanceof WishlistInterface);

        $flashBag = $request->getSession()->getFlashBag();
        $redirect = $this->redirectHandler->redirectToRoute($configuration, (string) $configuration->getRedirectRoute('show'));

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
}
