<?php

namespace SymfonyCorp\Connect\Security\User;

use SymfonyCorp\Connect\Api\Entity\User;

interface ConnectUserInterface
{
    public function updateFromConnectUser(User $user): void;
}
