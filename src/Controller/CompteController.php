<?php

namespace App\Controller;

use App\Entity\CompteBancaire;
use App\Form\CompteType;
use App\Service\BanqueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/comptes')]
class CompteController extends AbstractController
{
    #[Route('/', name: 'app_comptes_list', methods: ['GET'])]
    public function list(): Response
    {
        $user = $this->getUser();

        return $this->render('compte/list.html.twig', [
            'comptes' => $user->getComptesBancaires(),
        ]);
    }

    #[Route('/nouveau', name: 'app_comptes_new', methods: ['GET', 'POST'])]
    public function new(Request $request, BanqueService $banqueService): Response
    {
        $form = $this->createForm(CompteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $type = (string) $form->get('type')->getData();
                $compte = $banqueService->creerCompte($this->getUser(), $type);

                $this->addFlash('success', '✅ Compte ' . $compte->getTypeLabel() . ' créé avec succès !');

                return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('compte/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comptes_show', methods: ['GET'])]
    public function show(CompteBancaire $compte): Response
    {
        $this->checkOwnership($compte);

        return $this->render('compte/show.html.twig', [
            'compte' => $compte,
            'transactions' => $compte->getAllTransactions(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_comptes_delete', methods: ['POST'])]
    public function delete(CompteBancaire $compte, BanqueService $banqueService, Request $request): Response
    {
        $this->checkOwnership($compte);

        if (!$this->isCsrfTokenValid('delete_compte_' . $compte->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_comptes_list');
        }

        try {
            $banqueService->supprimerCompte($compte);
            $this->addFlash('success', '🗑️ Compte supprimé avec succès.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_comptes_list');
    }

    private function checkOwnership(CompteBancaire $compte): void
    {
        if ($compte->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès refusé à ce compte.');
        }
    }
}