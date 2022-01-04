<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportTrait;
use SymfonyCorp\Connect\Api\Entity\User;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ConnectPassport extends Passport
{
    use PassportTrait;

    protected $user;

    private $apiUser;
    private $accessToken;
    private $scope;

    /**
     * @param BadgeInterface[] $badges
     */
    public function __construct(UserInterface $user, User $apiUser, string $accessToken, string $scope, array $badges = [])
    {
        $this->user = $user;
        $this->apiUser = $apiUser;
        $this->accessToken = $accessToken;
        $this->scope = $scope;

        foreach ($badges as $badge) {
            $this->addBadge($badge);
        }
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getApiUser(): User
    {
        return $this->apiUser;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getScope(): string
    {
        return $this->scope;
    }
}
