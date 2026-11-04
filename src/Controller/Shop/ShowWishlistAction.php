<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Controller\Shop;

use PsSummit\SyliusWishlistPlugin\Context\WishlistContextInterface;
use PsSummit\SyliusWishlistPlugin\View\WishlistViewFactory;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;

#[AsController]
final class ShowWishlistAction
{
    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
        private readonly WishlistViewFactory $wishlistViewFactory,
        private readonly ChannelContextInterface $channelContext,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(): Response
    {
        $channel = $this->channelContext->getChannel();
        \assert($channel instanceof ChannelInterface);

        // findWishlist(): viewing the page never creates a wishlist.
        $wishlist = $this->wishlistViewFactory->create($this->wishlistContext->findWishlist(), $channel);

        return new Response($this->twig->render('@PsSummitSyliusWishlistPlugin/shop/wishlist/show.html.twig', [
            'wishlist' => $wishlist,
        ]));
    }
}
