<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Twig;

use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
use PsSummit\SyliusWishlistPlugin\View\WishlistView;
use PsSummit\SyliusWishlistPlugin\View\WishlistViewFactory;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class WishlistExtension extends AbstractExtension
{
    public function __construct(
        private readonly WishlistViewFactory $wishlistViewFactory,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // Display data of the wishlist resource: items with name, image, price, available.
            new TwigFunction('ps_summit_wishlist_view', $this->createView(...)),
        ];
    }

    public function createView(?WishlistInterface $wishlist): WishlistView
    {
        $channel = $this->channelContext->getChannel();
        \assert($channel instanceof ChannelInterface);

        return $this->wishlistViewFactory->create($wishlist, $channel);
    }
}
