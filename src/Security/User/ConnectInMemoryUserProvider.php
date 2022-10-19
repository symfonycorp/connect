<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * ConnectInMemoryUserProvider is a simple non persistent user provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ConnectInMemoryUserProvider implements UserProviderInterface
{
    private $users;

    /**
     * @param array $users a hash where the keys are usernames and the values are the user roles
     */
    public function __construct(array $users = [])
    {
        foreach ($users as $username => $roles) {
            $this->users[$username] = new InMemoryUser($username, '', (array) $roles, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByIdentifier($username): UserInterface
    {
        $user = $this->users[$username] ?? new InMemoryUser($username, '', ['ROLE_CONNECT_USER'], true);

        return new InMemoryUser($user->getUserIdentifier(), $user->getPassword(), $user->getRoles(), $user->isEnabled());
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof UserInterface) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class): bool
    {
        return InMemoryUser::class === $class;
    }
}
