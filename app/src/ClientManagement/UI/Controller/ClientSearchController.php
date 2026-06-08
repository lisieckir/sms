<?php

declare(strict_types=1);

namespace App\ClientManagement\UI\Controller;

use App\ClientManagement\Domain\Model\ClientRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clients')]
class ClientSearchController extends AbstractController
{
    #[Route('/search', name: 'app_client_search')]
    public function search(Request $request, ClientRepositoryInterface $clientRepository): JsonResponse
    {
        $q = $request->query->get('q', '');
        if (strlen($q) < 1) {
            $clients = $clientRepository->findAll();
        } else {
            $clients = $clientRepository->searchByTerm($q);
        }

        $results = array_map(fn($client) => [
            'id' => $client->id()->value(),
            'name' => $client->name(),
        ], $clients);

        return $this->json($results);
    }
}
