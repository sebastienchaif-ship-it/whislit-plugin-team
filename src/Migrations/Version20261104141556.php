<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104141556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create wishlist and wishlist item tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ps_summit_wishlist (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, customer_id INT DEFAULT NULL, channel_id INT NOT NULL, INDEX IDX_D9322D379395C3F3 (customer_id), INDEX IDX_D9322D3772F5A1AA (channel_id), UNIQUE INDEX ps_summit_wishlist_customer_channel_unique (customer_id, channel_id), UNIQUE INDEX ps_summit_wishlist_token_unique (token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ps_summit_wishlist_item (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, wishlist_id INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_747209D8FB8E54CD (wishlist_id), INDEX IDX_747209D83B69A9AF (variant_id), UNIQUE INDEX ps_summit_wishlist_item_wishlist_variant_unique (wishlist_id, variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ps_summit_wishlist ADD CONSTRAINT FK_D9322D379395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ps_summit_wishlist ADD CONSTRAINT FK_D9322D3772F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ps_summit_wishlist_item ADD CONSTRAINT FK_747209D8FB8E54CD FOREIGN KEY (wishlist_id) REFERENCES ps_summit_wishlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ps_summit_wishlist_item ADD CONSTRAINT FK_747209D83B69A9AF FOREIGN KEY (variant_id) REFERENCES sylius_product_variant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ps_summit_wishlist DROP FOREIGN KEY FK_D9322D379395C3F3');
        $this->addSql('ALTER TABLE ps_summit_wishlist DROP FOREIGN KEY FK_D9322D3772F5A1AA');
        $this->addSql('ALTER TABLE ps_summit_wishlist_item DROP FOREIGN KEY FK_747209D8FB8E54CD');
        $this->addSql('ALTER TABLE ps_summit_wishlist_item DROP FOREIGN KEY FK_747209D83B69A9AF');
        $this->addSql('DROP TABLE ps_summit_wishlist');
        $this->addSql('DROP TABLE ps_summit_wishlist_item');
    }
}
