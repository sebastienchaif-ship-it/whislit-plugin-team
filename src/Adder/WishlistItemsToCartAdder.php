<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Adder;

use PsSummit\SyliusWishlistPlugin\Checker\WishlistVariantAvailabilityChecker;
use PsSummit\SyliusWishlistPlugin\Entity\WishlistInterface;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Adds wishlist items to the native Sylius cart (quantity 1 each), through the same
 * services and validation as the product page "Add to cart". Items stay in the wishlist.
 */
final class WishlistItemsToCartAdder
{
    /** @param CartItemFactoryInterface<OrderItemInterface> $cartItemFactory */
    public function __construct(
        private readonly CartContextInterface $cartContext,
        #[Autowire(service: 'sylius.factory.order_item')]
        private readonly CartItemFactoryInterface $cartItemFactory,
        private readonly OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private readonly AddToCartCommandFactoryInterface $addToCartCommandFactory,
        private readonly CartItemAdderInterface $cartItemAdder,
        private readonly ValidatorInterface $validator,
        private readonly WishlistVariantAvailabilityChecker $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    /**
     * Only items of the given wishlist are considered: other ids are ignored.
     *
     * @param list<int|string> $itemIds
     *
     * @return array{selected: int, added: int, skipped: int}
     */
    public function add(WishlistInterface $wishlist, array $itemIds): array
    {
        $itemIds = array_map('strval', $itemIds);
        $items = array_values(array_filter(
            $wishlist->getItems()->toArray(),
            static fn (WishlistItemInterface $item): bool => in_array((string) $item->getId(), $itemIds, true),
        ));

        $result = ['selected' => count($items), 'added' => 0, 'skipped' => 0];
        if ([] === $items) {
            return $result;
        }

        $channel = $this->channelContext->getChannel();
        \assert($channel instanceof ChannelInterface);
        $cart = $this->cartContext->getCart();
        \assert($cart instanceof OrderInterface);

        foreach ($items as $item) {
            $this->addItem($item, $cart, $channel) ? ++$result['added'] : ++$result['skipped'];
        }

        return $result;
    }

    private function addItem(WishlistItemInterface $item, OrderInterface $cart, ChannelInterface $channel): bool
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
