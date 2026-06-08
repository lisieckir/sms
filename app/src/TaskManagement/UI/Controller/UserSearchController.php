<?php

declare(strict_types=1);

namespace App\TaskManagement\UI\Controller;

use App\IdentityAccess\Domain\Model\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users')]
class UserSearchController extends AbstractController
{
    #[Route('/search', name: 'app_user_search')]
    public function search(Request $request, UserRepositoryInterface $userRepository): JsonResponse
    {
        $q = $request->query->get('q', '');
        if (strlen($q) < 1) {
            return $this->json([]);
        }

        $users = $userRepository->searchByTerm($q);

        $results = array_map(fn($user) => [
            'id' => $user->id()->value(),
            'name' => $user->name()->fullName(),
            'username' => $user->username(),
        ], $users);

        return $this->json($results);
    }
}
