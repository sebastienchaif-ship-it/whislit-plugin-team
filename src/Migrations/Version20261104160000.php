<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Stage 2 (multiple named wishlists), upgrading a Stage 1 database in place:
 * - adds the "name" column and gives the existing wishlists the default name "My Favorites";
 * - drops the unique constraints on (customer, channel) and on token, since a shopper now has several wishlists
 *   (token keeps a plain index for the guest lookups);
 * - keeps the unique (wishlist, variant) on the items.
 */
final class Version20261104160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stage 2: named wishlists (existing ones become "My Favorites"), several wishlists per customer or guest';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ps_summit_wishlist ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE ps_summit_wishlist SET name = 'My Favorites' WHERE name IS NULL");
        $this->addSql('ALTER TABLE ps_summit_wishlist MODIFY name VARCHAR(255) NOT NULL');

        $this->addSql('DROP INDEX ps_summit_wishlist_customer_channel_unique ON ps_summit_wishlist');
        $this->addSql('DROP INDEX ps_summit_wishlist_token_unique ON ps_summit_wishlist');
        $this->addSql('CREATE INDEX ps_summit_wishlist_token_idx ON ps_summit_wishlist (token)');
    }

    public function down(Schema $schema): void
    {
        // Fails if a customer or a guest has several wishlists in a channel: keep only one of them before going back.
        $this->addSql('DROP INDEX ps_summit_wishlist_token_idx ON ps_summit_wishlist');
        $this->addSql('CREATE UNIQUE INDEX ps_summit_wishlist_token_unique ON ps_summit_wishlist (token)');
        $this->addSql('CREATE UNIQUE INDEX ps_summit_wishlist_customer_channel_unique ON ps_summit_wishlist (customer_id, channel_id)');
        $this->addSql('ALTER TABLE ps_summit_wishlist DROP name');
    }
}
