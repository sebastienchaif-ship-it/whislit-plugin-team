<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * "Add to wishlist" form used by the ResourceController createAction.
 * No fields (the variant comes from the route): it only carries the CSRF token.
 * Nameless, with the token in "_csrf_token", so the front-end buttons post a plain
 * <input name="_csrf_token" value="{{ csrf_token('ps_summit_wishlist_add') }}">.
 */
final class WishlistItemType extends AbstractResourceType
{
    public const CSRF_TOKEN_ID = 'ps_summit_wishlist_add';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => self::CSRF_TOKEN_ID,
            'allow_extra_fields' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
