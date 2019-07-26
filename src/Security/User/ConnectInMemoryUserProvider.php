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
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * InMemoryUserProvider is a simple non persistent user provider.
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
            $this->users[$username] = new User($username, '', (array) $roles, true, true, true, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username): User
    {
        $user = $this->users[$username] ?? new User($username, '', ['ROLE_CONNECT_USER'], true, true, true, true);

        return new User($user->getUsername(), $user->getPassword(), $user->getRoles(), $user->isEnabled(), $user->isAccountNonExpired(), $user->isCredentialsNonExpired(), $user->isAccountNonLocked());
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class): bool
    {
        return User::class === $class;
    }
}
