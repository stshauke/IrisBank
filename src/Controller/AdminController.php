<?php

namespace App\Controller;

use App\Entity\CompteBancaire;
use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\CompteBancaireRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository $userRepo,
        CompteBancaireRepository $compteRepo,
        TransactionRepository $transactionRepo
    ): Response {
        $totalClients = $userRepo->countClients();
        $totalComptes = $compteRepo->count([]);
        $totalDepots = $transactionRepo->sumDepots();
        $recentUsers = $userRepo->findRecentUsers(5);

        return $this->render('admin/dashboard.html.twig', [
            'totalClients' => $totalClients,
            'totalComptes' => $totalComptes,
            'totalDepots' => $totalDepots,
            'recentUsers' => $recentUsers,
        ]);
    }

    #[Route('/clients', name: 'admin_clients')]
    public function clients(Request $request, UserRepository $userRepo): Response
    {
        $search = $request->query->get('search', '');
        $clients = $userRepo->searchClients($search);

        return $this->render('admin/clients.html.twig', [
            'clients' => $clients,
            'search' => $search,
        ]);
    }

    #[Route('/clients/{id}', name: 'admin_client_show')]
    public function clientShow(User $client): Response
    {
        return $this->render('admin/client_show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/clients/{id}/modifier', name: 'admin_client_edit')]
    public function clientEdit(User $client, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AdminUserType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '✅ Client modifié avec succès.');
            return $this->redirectToRoute('admin_client_show', ['id' => $client->getId()]);
        }

        return $this->render('admin/client_edit.html.twig', [
            'form' => $form,
            'client' => $client,
        ]);
    }

    #[Route('/clients/{id}/supprimer', name: 'admin_client_delete', methods: ['POST'])]
    public function clientDelete(User $client, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_user_' . $client->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_clients');
        }

        $em->remove($client);
        $em->flush();
        $this->addFlash('success', '🗑️ Client supprimé.');
        return $this->redirectToRoute('admin_clients');
    }

    #[Route('/comptes', name: 'admin_comptes')]
    public function comptes(CompteBancaireRepository $compteRepo): Response
    {
        $comptes = $compteRepo->findAll();
        return $this->render('admin/comptes.html.twig', ['comptes' => $comptes]);
    }

    #[Route('/comptes/{id}/toggle', name: 'admin_compte_toggle', methods: ['POST'])]
    public function toggleCompte(CompteBancaire $compte, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('toggle_compte_' . $compte->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_comptes');
        }

        if ($compte->isActif()) {
            $compte->setStatut(CompteBancaire::STATUS_BLOQUE);
            $this->addFlash('warning', '🔒 Compte bloqué.');
        } else {
            $compte->setStatut(CompteBancaire::STATUS_ACTIF);
            $this->addFlash('success', '🔓 Compte débloqué.');
        }

        $em->flush();
        return $this->redirectToRoute('admin_comptes');
    }

    #[Route('/comptes/{id}/transactions', name: 'admin_compte_transactions')]
    public function compteTransactions(CompteBancaire $compte): Response
    {
        return $this->render('admin/compte_transactions.html.twig', [
            'compte' => $compte,
            'transactions' => $compte->getAllTransactions(),
        ]);
    }
}
