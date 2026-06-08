<?php

declare(strict_types=1);

namespace App\IdentityAccess\UI\Controller;

use App\IdentityAccess\Application\Command\ChangePassword\ChangePasswordCommand;
use App\IdentityAccess\Infrastructure\Security\UserIdentity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, MessageBusInterface $commandBus): Response
    {
        if ($request->isMethod('POST')) {
            $userIdentity = $this->getUser();
            if ($userIdentity instanceof UserIdentity) {
                $command = new ChangePasswordCommand(
                    userId: $userIdentity->getUserId(),
                    oldPassword: $request->request->get('oldPassword'),
                    newPassword: $request->request->get('newPassword'),
                );
                $commandBus->dispatch($command);
                $this->addFlash('success', 'Password changed successfully');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('identity_access/profile.html.twig');
    }
}
