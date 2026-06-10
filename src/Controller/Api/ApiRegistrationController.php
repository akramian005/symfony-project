<?php

// Файл находится в: src/Controller/Api/ApiRegistrationController.php
namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiRegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Переданы неполные данные. Обязательные поля: email, password'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // При регистрации генерируем стартовый токен
        $generatedToken = bin2hex(random_bytes(32));
        $user->setApiToken($generatedToken);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'status' => 'Пользователь успешно создан через API',
            'api_token' => $generatedToken
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Обязательные поля: email, password'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Ищем пользователя по email
        $user = $userRepository->findOneBy(['email' => $data['email']]);

        // Проверяем, существует ли пользователь и совпадает ли хэш пароля
        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'error' => 'Неверные учетные данные'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // При каждом входе генерируем новый токен безопасности
        $newToken = bin2hex(random_bytes(32));
        $user->setApiToken($newToken);

        $entityManager->flush();

        return new JsonResponse([
            'status' => 'Успешная авторизация',
            'api_token' => $newToken
        ], Response::HTTP_OK);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Защита: если токен не прислали или он невалидный,
        // Symfony (благодаря security.yaml) вернет 401 до того, как запрос попадет сюда.
        if (!$user) {
            return new JsonResponse(['error' => 'Вы не авторизованы'], Response::HTTP_UNAUTHORIZED);
        }

        // Стираем токен в базе данных, делая его бесполезным
        $user->setApiToken(null);
        $entityManager->flush();


        return new JsonResponse([
            'status' => 'Успешный выход. Токен аннулирован.'
        ], Response::HTTP_OK);
    }

}
