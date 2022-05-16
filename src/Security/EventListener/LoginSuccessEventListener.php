<?php

namespace SymfonyCorp\Connect\Security\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use SymfonyCorp\Connect\Security\Authentication\Token\ConnectToken;
use SymfonyCorp\Connect\Security\User\ConnectUserInterface;

class LoginSuccessEventListener implements EventSubscriberInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em = null)
    {
        $this->em = $em;
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        if (null === $this->em) {
            return;
        }

        $token = $event->getAuthenticatedToken();
        if (!$token instanceof ConnectToken) {
            return;
        }

        $user = $event->getUser();
        if (!$user instanceof ConnectUserInterface) {
            return;
        }

        $user->updateFromConnectUser($token->getApiUser(), $token->getAccessToken());

        $this->em->persist($user);
        $this->em->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
}
