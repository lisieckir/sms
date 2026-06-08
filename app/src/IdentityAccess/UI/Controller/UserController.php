<?php

declare(strict_types=1);

namespace App\IdentityAccess\UI\Controller;

use App\IdentityAccess\Application\Command\RegisterUser\RegisterUserCommand;
use App\IdentityAccess\Application\Command\ToggleUserStatus\ToggleUserStatusCommand;
use App\IdentityAccess\Application\Command\UpdateUser\UpdateUserCommand;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_user_list')]
    public function index(UserRepositoryInterface $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('identity_access/user_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/create', name: 'app_user_create')]
    public function create(Request $request, MessageBusInterface $commandBus): Response
    {
        if ($request->isMethod('POST')) {
            $command = new RegisterUserCommand(
                firstName: $request->request->get('firstName'),
                lastName: $request->request->get('lastName'),
                email: $request->request->get('email'),
                username: $request->request->get('username'),
                plainPassword: $request->request->get('plainPassword'),
                isAdmin: $request->request->getBoolean('isAdmin'),
            );
            $commandBus->dispatch($command);
            $this->addFlash('success', 'User created successfully');
            return $this->redirectToRoute('app_user_list');
        }

        return $this->render('identity_access/user_form.html.twig', [
            'user' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit')]
    public function edit(string $id, Request $request, MessageBusInterface $commandBus, UserRepositoryInterface $userRepository): Response
    {
        $user = $userRepository->findById(new UserId($id));
        if ($user === null) {
            throw $this->createNotFoundException('User not found');
        }

        if ($request->isMethod('POST')) {
            $commandBus->dispatch(new UpdateUserCommand(
                userId: $id,
                email: $request->request->get('email'),
                isAdmin: $request->request->getBoolean('isAdmin'),
            ));
            $this->addFlash('success', 'User updated successfully');
            return $this->redirectToRoute('app_user_list');
        }

        return $this->render('identity_access/user_edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'app_user_toggle_active', methods: ['POST'])]
    public function toggleActive(string $id, MessageBusInterface $commandBus): Response
    {
        $commandBus->dispatch(new ToggleUserStatusCommand(userId: $id));
        $this->addFlash('success', 'User status updated');
        return $this->redirectToRoute('app_user_list');
    }
}
