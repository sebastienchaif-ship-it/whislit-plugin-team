<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/** Adds "Wishlists" to the Marketing section of the admin main menu. */
#[AsEventListener(event: 'sylius.menu.admin.main')]
final class AdminMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
    {
        $marketing = $event->getMenu()->getChild('marketing');
        if (null === $marketing) {
            return;
        }

        $marketing
            ->addChild('ps_summit_wishlists', ['route' => 'ps_summit_wishlist_admin_wishlist_index'])
            ->setLabel('ps_summit_wishlist.ui.wishlists')
            ->setLabelAttribute('icon', 'tabler:heart')
        ;
    }
}
