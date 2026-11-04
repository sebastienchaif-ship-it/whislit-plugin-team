<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

#[ORM\Entity]
#[ORM\Table(name: 'ps_summit_wishlist')]
#[ORM\UniqueConstraint(name: 'ps_summit_wishlist_customer_channel_unique', columns: ['customer_id', 'channel_id'])]
#[ORM\UniqueConstraint(name: 'ps_summit_wishlist_token_unique', columns: ['token'])]
class Wishlist implements WishlistInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    /** Guest identifier stored in a cookie; null for customer wishlists. */
    #[ORM\Column(name: 'token', type: 'string', length: 64, nullable: true)]
    private ?string $token = null;

    #[ORM\ManyToOne(targetEntity: CustomerInterface::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?CustomerInterface $customer = null;

    #[ORM\ManyToOne(targetEntity: ChannelInterface::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ChannelInterface $channel = null;

    /** @var Collection<int, WishlistItemInterface> */
    #[ORM\OneToMany(mappedBy: 'wishlist', targetEntity: WishlistItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $items;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    public function getCustomer(): ?CustomerInterface
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerInterface $customer): void
    {
        $this->customer = $customer;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function hasItems(): bool
    {
        return !$this->items->isEmpty();
    }

    public function hasItem(WishlistItemInterface $item): bool
    {
        return $this->items->contains($item);
    }

    public function addItem(WishlistItemInterface $item): void
    {
        if ($this->hasItem($item)) {
            return;
        }

        $variant = $item->getVariant();
        if (null !== $variant && $this->hasVariant($variant)) {
            return;
        }

        $item->setWishlist($this);
        $this->items->add($item);
        $this->touch();
    }

    public function removeItem(WishlistItemInterface $item): void
    {
        if (!$this->hasItem($item)) {
            return;
        }

        $this->items->removeElement($item);
        $item->setWishlist(null);
        $this->touch();
    }

    public function hasVariant(ProductVariantInterface $variant): bool
    {
        return null !== $this->getItemByVariant($variant);
    }

    public function getItemByVariant(ProductVariantInterface $variant): ?WishlistItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->getVariant() === $variant) {
                return $item;
            }
        }

        return null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
