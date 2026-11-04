<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Create / rename a wishlist (ResourceController createAction and updateAction).
 * Nameless form, so the front end posts plain "name" and "_csrf_token" fields;
 * the CSRF token id is set per route (_sylius.form.options.csrf_token_id).
 */
final class WishlistType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, [
            'label' => 'sylius.ui.name',
            'empty_data' => '',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'ps_summit_wishlist_create',
            'allow_extra_fields' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
