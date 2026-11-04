# Sylius Wishlist Plugin

Wishlist plugin for Sylius 2, built by our team at the PS Summit Hackathon 2026 (Lyon, 4 November 2026).

Status: empty base, no feature yet. Stage 1 (basic wishlist) comes next.

## Requirements

- PHP 8.2+
- Sylius 2.x (developed on Sylius Standard 2.3.0)

## Installation

1. Add the repository and require the plugin in the host application:

   ```bash
   composer config repositories.wishlist vcs https://github.com/sebastienchaif-ship-it/whislit-plugin-team
   composer require ps-summit/sylius-wishlist-plugin:dev-main
   ```

   For local development (plugin cloned into `sylius/plugins/WishlistPlugin`), from the Sylius root:

   ```bash
   docker compose exec -T php composer config repositories.wishlist path plugins/WishlistPlugin
   docker compose exec -T php composer require ps-summit/sylius-wishlist-plugin:*@dev
   ```

2. Enable the bundle in `config/bundles.php`:

   ```php
   PsSummit\SyliusWishlistPlugin\PsSummitSyliusWishlistPlugin::class => ['all' => true],
   ```

3. Import the plugin configuration, e.g. in `config/packages/ps_summit_wishlist.yaml`:

   ```yaml
   imports:
       - { resource: "@PsSummitSyliusWishlistPlugin/config/config.yaml" }
   ```

4. Run the migrations (the plugin registers its own migrations path automatically) and clear the cache:

   ```bash
   docker compose exec -T php bin/console doctrine:migrations:migrate -n
   docker compose exec -T php bin/console cache:clear
   ```

## Data model

| Resource | Service ids | Table |
|---|---|---|
| `ps_summit_wishlist.wishlist` | `ps_summit_wishlist.repository.wishlist`, `ps_summit_wishlist.factory.wishlist` | `ps_summit_wishlist` |
| `ps_summit_wishlist.wishlist_item` | `ps_summit_wishlist.repository.wishlist_item`, `ps_summit_wishlist.factory.wishlist_item` | `ps_summit_wishlist_item` |

- A `Wishlist` belongs to a channel and either to a customer (`customer`) or to a guest (`token`, stored in a cookie). Unique on `(customer, channel)` and on `token`.
- A `WishlistItem` points to a `ProductVariant`. Unique on `(wishlist, variant)`, and `Wishlist::addItem()` silently skips a variant that is already there.
- Lookups: `WishlistRepositoryInterface::findOneByCustomerAndChannel()` and `findOneByTokenAndChannel()` (items and variants are fetch-joined).
- Deleting a customer, channel or variant cascades to the wishlist rows.

## Upgrade

Each stage is released as a new version of the plugin. Database changes are shipped as Doctrine migrations, so an upgrade is:

```bash
composer update ps-summit/sylius-wishlist-plugin
bin/console doctrine:migrations:migrate -n
bin/console cache:clear
```

## License

MIT
