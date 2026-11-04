# Sylius Wishlist Plugin

Wishlist plugin for Sylius 2, built by our team at the PS Summit Hackathon 2026 (Lyon, 4 November 2026).

Status: Stage 1 (basic wishlist) done; Stage 2 (multiple named wishlists) in progress.

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

4. Import the shop and admin routes, e.g. in `config/routes/ps_summit_wishlist.yaml`:

   ```yaml
   ps_summit_wishlist_shop:
       resource: "@PsSummitSyliusWishlistPlugin/config/routes/shop.yaml"
       prefix: /{_locale}
       requirements:
           _locale: ^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$

   ps_summit_wishlist_admin:
       resource: "@PsSummitSyliusWishlistPlugin/config/routes/admin.yaml"
       prefix: '/%sylius_admin.path_name%'
   ```

5. Run the migrations (the plugin registers its own migrations path automatically) and clear the cache:

   ```bash
   docker compose exec -T php bin/console doctrine:migrations:migrate -n
   docker compose exec -T php bin/console cache:clear
   ```

## Data model

| Resource | Service ids | Table |
|---|---|---|
| `ps_summit_wishlist.wishlist` | `ps_summit_wishlist.repository.wishlist`, `ps_summit_wishlist.factory.wishlist` | `ps_summit_wishlist` |
| `ps_summit_wishlist.wishlist_item` | `ps_summit_wishlist.repository.wishlist_item`, `ps_summit_wishlist.factory.wishlist_item` | `ps_summit_wishlist_item` |

- A `Wishlist` has a `name` and belongs to a channel and either to a customer (`customer`) or to a guest (`token`, stored in a cookie). A customer or a guest can have several wishlists: the guest token is shared by all of them (plain index, not unique).
- `name` is required: `NotBlank` (whitespace-only is rejected, the value is stored trimmed) and at most 255 characters, validation group `sylius`, messages in the `validators` domain (en, fr, it). New wishlists default to `WishlistInterface::DEFAULT_NAME` ("My Favorites").
- A `WishlistItem` points to a `ProductVariant`. Unique on `(wishlist, variant)`, and `Wishlist::addItem()` silently skips a variant that is already there. The same variant can be in several wishlists.
- Lookups (oldest wishlist first, items and variants fetch-joined): `WishlistRepositoryInterface::findAllByCustomerAndChannel()` and `findAllByTokenAndChannel()`; `findOneByCustomerAndChannel()` and `findOneByTokenAndChannel()` return the oldest one (the default wishlist).
- Deleting a customer, channel or variant cascades to the wishlist rows.

## Admin

**Marketing → Wishlists** (`ps_summit_wishlist_admin_wishlist_index`, `/admin/wishlists/`) lists every wishlist, read-only: name, owner (customer name and email, or "Guest"), channel, number of products, creation and last update dates. Filters: name, customer (email, first or last name), customer/guest wishlist, channel. Grid: `ps_summit_wishlist_admin_wishlist` (`config/grids/admin/wishlist.yaml`).

## Upgrade

Each stage is released as a new version of the plugin. Database changes are shipped as Doctrine migrations, so an upgrade is:

```bash
composer update ps-summit/sylius-wishlist-plugin
bin/console doctrine:migrations:migrate -n
bin/console cache:clear
```

### Stage 1 → Stage 2 (multiple named wishlists)

1. Update the plugin and run the migrations:

   ```bash
   composer update ps-summit/sylius-wishlist-plugin
   bin/console doctrine:migrations:migrate -n
   bin/console cache:clear
   ```

   and add the `ps_summit_wishlist_admin` route import (installation step 4) for the new admin wishlist listing.

2. `Version20261104160000` changes the `ps_summit_wishlist` table in place (it is not recreated, no data is lost):
   - adds the `name` column; every existing wishlist is named "My Favorites", with its products unchanged;
   - drops the unique indexes `ps_summit_wishlist_customer_channel_unique` and `ps_summit_wishlist_token_unique` (a shopper now has several wishlists) and adds a plain index on `token`;
   - keeps the unique `(wishlist, variant)` on `ps_summit_wishlist_item`.

3. Check that the Stage 1 products are now in "My Favorites":

   ```bash
   bin/console dbal:run-sql "SELECT w.id, w.name, COUNT(i.id) AS items FROM ps_summit_wishlist w LEFT JOIN ps_summit_wishlist_item i ON i.wishlist_id = w.id GROUP BY w.id"
   ```

Going back (`doctrine:migrations:migrate prev`) restores the unique indexes, so it fails while a customer or a guest has more than one wishlist in a channel.

## License

MIT
