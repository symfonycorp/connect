<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

if (\PHP_VERSION_ID >= 70100 && method_exists(AbstractToken::class, '__serialize')) {
eval(<<<'EOPHP'
    namespace SensioLabs\Connect\Security\Authentication\Token;

    /**
     * @internal
     */
    trait SerializationConnectTokenTrait
    {
        public function __serialize(): array
        {
            return [$this->apiUser, $this->accessToken, $this->providerKey, $this->scope, parent::__serialize()];
        }

        public function __unserialize(array $data): void
        {
            list($this->apiUser, $this->accessToken, $this->providerKey, $this->scope) = $data;
            parent::__serialize($data);
        }
    }
EOPHP
);
} else {
    /**
     * @internal
     */
    trait SerializationConnectTokenTrait
    {
        public function serialize()
        {
            return serialize([$this->apiUser, $this->accessToken, $this->providerKey, $this->scope, parent::serialize()]);
        }

        public function unserialize($str)
        {
            list($this->apiUser, $this->accessToken, $this->providerKey, $this->scope, $parentStr) = unserialize($str);
            
            parent::unserialize($parentStr);
        }
    }
}
