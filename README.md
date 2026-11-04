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

   For local development, a path repository works too:

   ```bash
   composer config repositories.wishlist path ../whislit-plugin-team
   composer require ps-summit/sylius-wishlist-plugin:*@dev
   ```

2. Enable the bundle in `config/bundles.php`:

   ```php
   PsSummit\SyliusWishlistPlugin\PsSummitSyliusWishlistPlugin::class => ['all' => true],
   ```

3. Clear the cache:

   ```bash
   bin/console cache:clear
   ```

## Upgrade

Each stage is released as a new version of the plugin. Database changes are shipped as Doctrine migrations, so an upgrade is:

```bash
composer update ps-summit/sylius-wishlist-plugin
bin/console doctrine:migrations:migrate -n
bin/console cache:clear
```

## License

MIT
