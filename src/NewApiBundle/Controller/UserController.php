<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\UserCreateInputType;
use NewApiBundle\InputType\UserEditInputType;
use NewApiBundle\InputType\UserFilterInputType;
use NewApiBundle\InputType\UserInitializeInputType;
use NewApiBundle\InputType\UserOrderInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Entity\User;
use UserBundle\Repository\UserRepository;
use UserBundle\Utils\UserService;

class UserController extends AbstractController
{
    /**
     * @Rest\Get("/users/{id}")
     *
     * @param User $object
     *
     * @return JsonResponse
     */
    public function item(User $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Get("/users")
     *
     * @param UserOrderInputType $userOderInputType
     * @param UserFilterInputType $userFilterInputType
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function list(UserOrderInputType $userOderInputType, UserFilterInputType $userFilterInputType, Pagination $pagination): JsonResponse
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        $users = $userRepository->findByParams($userOderInputType, $userFilterInputType, $pagination);

        return $this->json($users);
    }

    /**
     * @Rest\Post("/users/initialize")
     *
     * @param UserInitializeInputType $inputType
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function initialize(UserInitializeInputType $inputType): JsonResponse
    {
        $initializedUser = $this->get('user.user_service')->initialize($inputType);

        return $this->json($initializedUser);
    }

    /**
     * @Rest\Post("/users/{id}")
     *
     * @param User                $user
     * @param UserCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(User $user, UserCreateInputType $inputType): JsonResponse
    {
        /** @var UserService $userService */
        $userService = $this->get('user.user_service');

        $user = $userService->create($user, $inputType);

        return $this->json($user);
    }

    /**
     * @Rest\Put("/users/{id}")
     *
     * @param User $user
     * @param UserEditInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(User $user, UserEditInputType $inputType): JsonResponse
    {
        /** @var UserService $userService */
        $userService = $this->get('user.user_service');

        $updatedUser = $userService->update($user, $inputType);

        return $this->json($updatedUser);
    }

    /**
     * @Rest\Delete("/users/{id}")
     *
     * @param User $user
     *
     * @return JsonResponse
     */
    public function delete(User $user): JsonResponse
    {
        $this->get('user.user_service')->remove($user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/users/salt/{username}")
     *
     * @param string $username
     *
     * @return JsonResponse
     */
    public function getSalt(string $username): JsonResponse
    {
        $salt = $this->get('user.user_service')->getSalt($username);

        return $this->json($salt);
    }
}