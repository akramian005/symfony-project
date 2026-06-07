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

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        // Если заголовок Authorization отсутствует или пустой — значит пришел гость
        if (empty($accessToken) || trim($accessToken) === '') {
            // Возвращаем пустой Badge. Symfony поймет, что пользователь анонимный,
            // не станет рубить запрос ошибкой 401 и передаст его в access_control.
            return new UserBadge('');
        }

        // Если токен передан — ищем пользователя в базе
        $user = $this->userRepository->findOneBy(['apiToken' => $accessToken]);

        if (!$user) {
            // Ошибка выбросится ТОЛЬКО если токен был передан, но он неверный
            throw new BadCredentialsException('Неверный или недействительный API-токен.');
        }

        // Возвращаем уникальный идентификатор (email) найденного юзера
        return new UserBadge($user->getUserIdentifier());
    }
}
