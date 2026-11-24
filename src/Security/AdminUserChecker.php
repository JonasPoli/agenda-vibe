<?php

namespace App\Security;

use App\Entity\Usuario;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Só administradores conseguem entrar: quem não tem ROLE_ADMIN é recusado no login,
 * sem abrir sessão (o access_control de /admin continua como segunda barreira).
 */
class AdminUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
    }

    /**
     * Roda depois de a senha ser conferida: assim a mensagem não revela, para quem
     * erra a senha, que o e-mail existe.
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if ($user instanceof Usuario && !$user->isAdmin()) {
            throw new CustomUserMessageAccountStatusException('Acesso restrito a administradores.');
        }
    }
}
