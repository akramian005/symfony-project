<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        // Ищем пользователя в базе по токену
        $user = $this->userRepository->findOneBy(['apiToken' => $accessToken]);

        if (!$user) {
            // Если токен не существует или невалиден
            throw new BadCredentialsException('Неверный или недействительный API-токен.');
        }

        // Возвращаем уникальный идентификатор (email) найденного юзера
        return new UserBadge($user->getUserIdentifier());
    }
}
