<?php

declare(strict_types=1);

namespace PsSummit\SyliusWishlistPlugin\EventListener;

use PsSummit\SyliusWishlistPlugin\Resolver\RedirectBackResolver;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Resource events of ps_summit_wishlist.wishlist (create / rename through createAction / updateAction).
 * On a POST these actions only reach "initialize" when the form is invalid: instead of rendering
 * a form template, go back to the page with the validation message (e.g. empty name) as error flash.
 */
final class WishlistListener
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly RedirectBackResolver $redirectBackResolver,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsEventListener(event: 'ps_summit_wishlist.wishlist.initialize_create')]
    #[AsEventListener(event: 'ps_summit_wishlist.wishlist.initialize_update')]
    public function onInvalidForm(ResourceControllerEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request || !$request->isMethod('POST')) {
            return;
        }

        // The submitted name is already mapped on the subject: validate it to get the message.
        $violations = $this->validator->validate($event->getSubject(), null, ['sylius']);
        $message = count($violations) > 0 ? (string) $violations->get(0)->getMessage() : 'ps_summit_wishlist.invalid_csrf_token';

        $request->getSession()->getFlashBag()->add('error', $message);
        $event->setResponse($this->redirectBackResolver->redirectBack($request));
    }
}
