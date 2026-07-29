<?php

declare(strict_types=1);

namespace App\ClientManagement\UI\Controller;

use App\ClientManagement\Application\Command\AddContact\AddContactCommand;
use App\ClientManagement\Application\Command\BlockClient\BlockClientCommand;
use App\ClientManagement\Application\Command\DeleteClient\DeleteClientCommand;
use App\ClientManagement\Application\Command\RegisterClient\RegisterClientCommand;
use App\ClientManagement\Application\Command\RestoreClient\RestoreClientCommand;
use App\ClientManagement\Application\Command\UpdateClient\UpdateClientCommand;
use App\ClientManagement\Application\Query\GetClientHandler;
use App\ClientManagement\Application\Query\GetClientQuery;
use App\ClientManagement\Application\Query\ListClientsHandler;
use App\ClientManagement\Application\Query\ListClientsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/clients')]
class ClientController extends AbstractController
{
    #[Route('', name: 'app_client_list')]
    public function index(ListClientsHandler $handler): Response
    {
        $clients = $handler(new ListClientsQuery());
        return $this->render('client/list.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/create', name: 'app_client_create')]
    public function create(Request $request, MessageBusInterface $commandBus): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $commandBus->dispatch(new RegisterClientCommand(
                nip: $request->request->get('nip'),
                name: $request->request->get('name'),
                address: $request->request->get('address'),
                country: $request->request->get('country'),
                email: $email !== '' ? $email : null,
                description: $request->request->get('description', ''),
                settlementType: $request->request->get('settlementType') ?: null,
            ));

            $this->addFlash('success', 'Client created');
            return $this->redirectToRoute('app_client_list');
        }

        return $this->render('client/form.html.twig', [
            'client' => null,
        ]);
    }

    #[Route('/{id}', name: 'app_client_show')]
    public function show(string $id, GetClientHandler $handler): Response
    {
        $client = $handler(new GetClientQuery($id));
        if ($client === null) {
            throw $this->createNotFoundException('Client not found');
        }

        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit')]
    public function edit(string $id, Request $request, MessageBusInterface $commandBus, GetClientHandler $handler): Response
    {
        $client = $handler(new GetClientQuery($id));
        if ($client === null) {
            throw $this->createNotFoundException('Client not found');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $commandBus->dispatch(new UpdateClientCommand(
                clientId: $id,
                name: $request->request->get('name'),
                address: $request->request->get('address'),
                country: $request->request->get('country'),
                email: $email !== '' ? $email : null,
                description: $request->request->get('description', ''),
                settlementType: $request->request->get('settlementType') ?: null,
            ));

            $this->addFlash('success', 'Client updated');
            return $this->redirectToRoute('app_client_show', ['id' => $id]);
        }

        return $this->render('client/form.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/toggle-block', name: 'app_client_toggle_block', methods: ['POST'])]
    public function toggleBlock(string $id, Request $request, MessageBusInterface $commandBus, GetClientHandler $handler): Response
    {
        $client = $handler(new GetClientQuery($id));
        if ($client === null) {
            throw $this->createNotFoundException('Client not found');
        }

        $commandBus->dispatch(new BlockClientCommand(
            clientId: $id,
            block: !$client->isBlocked(),
        ));

        $this->addFlash('success', 'Client status updated');
        return $this->redirectToRoute('app_client_list');
    }

    #[Route('/{id}/delete', name: 'app_client_delete', methods: ['POST'])]
    public function delete(string $id, MessageBusInterface $commandBus): Response
    {
        $commandBus->dispatch(new DeleteClientCommand($id));
        $this->addFlash('success', 'Client deleted');
        return $this->redirectToRoute('app_client_list');
    }

    #[Route('/{id}/restore', name: 'app_client_restore', methods: ['POST'])]
    public function restore(string $id, MessageBusInterface $commandBus): Response
    {
        $commandBus->dispatch(new RestoreClientCommand($id));
        $this->addFlash('success', 'Client restored');
        return $this->redirectToRoute('app_client_show', ['id' => $id]);
    }

    #[Route('/{id}/contact', name: 'app_client_add_contact', methods: ['POST'])]
    public function addContact(string $id, Request $request, MessageBusInterface $commandBus): Response
    {
        $commandBus->dispatch(new AddContactCommand(
            clientId: $id,
            firstName: $request->request->get('firstName'),
            lastName: $request->request->get('lastName'),
            email: $request->request->get('email'),
            phone: $request->request->get('phone') ?: null,
        ));

        $this->addFlash('success', 'Contact added');
        return $this->redirectToRoute('app_client_show', ['id' => $id]);
    }
}
