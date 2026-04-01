<?php
// ============================================================
// src/Controller/AdminController.php
// ============================================================
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
    // ──────────────────────────────────────────
    //  DASHBOARD
    // ──────────────────────────────────────────
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository           $userRepo,
        CompteBancaireRepository $compteRepo,
        TransactionRepository    $transactionRepo
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'totalClients' => $userRepo->countClients(),
            'totalComptes' => $compteRepo->count([]),
            'totalDepots'  => $transactionRepo->sumDepots(),
            'recentUsers'  => $userRepo->findRecentUsers(5),
        ]);
    }

    // ──────────────────────────────────────────
    //  LISTE CLIENTS (recherche avancée)
    // ──────────────────────────────────────────
    #[Route('/clients', name: 'admin_clients')]
    public function clients(Request $request, UserRepository $userRepo): Response
    {
        $search    = $request->query->get('search', '');
        $iban      = $request->query->get('iban', '');
        $nbComptes = $request->query->get('nb_comptes', '');

        $clients = $userRepo->searchClients($search, $iban, $nbComptes);

        return $this->render('admin/clients.html.twig', [
            'clients'    => $clients,
            'search'     => $search,
            'iban'       => $iban,
            'nb_comptes' => $nbComptes,
        ]);
    }

    // ──────────────────────────────────────────
    //  DÉTAIL CLIENT
    // ──────────────────────────────────────────
    #[Route('/clients/{id}', name: 'admin_client_show')]
    public function clientShow(User $client): Response
    {
        return $this->render('admin/client_show.html.twig', [
            'client' => $client,
        ]);
    }

    // ──────────────────────────────────────────
    //  MODIFIER CLIENT
    // ──────────────────────────────────────────
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
            'form'   => $form,
            'client' => $client,
        ]);
    }

    // ──────────────────────────────────────────
    //  SUPPRIMER CLIENT
    //
    //  Règles :
    //  1. Tous les comptes du client doivent avoir un solde = 0 €
    //  2. Si un seul compte a un solde > 0 → suppression bloquée
    // ──────────────────────────────────────────
    #[Route('/clients/{id}/supprimer', name: 'admin_client_delete', methods: ['POST'])]
    public function clientDelete(User $client, Request $request, EntityManagerInterface $em): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('delete_user_' . $client->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Token de sécurité invalide.');
            return $this->redirectToRoute('admin_client_show', ['id' => $client->getId()]);
        }

        // Vérifier que TOUS les comptes du client sont à 0 €
        $comptesNonVides = [];
        foreach ($client->getComptesBancaires() as $compte) {
            $solde = round((float) $compte->getSolde(), 2);
            if ($solde !== 0.0) {
                $comptesNonVides[] = sprintf(
                    '%s (solde : %.2f €)',
                    $compte->getTypeLabel(),
                    $solde
                );
            }
        }

        // Si au moins un compte a un solde > 0 → on bloque
        if (!empty($comptesNonVides)) {
            $this->addFlash(
                'error',
                '❌ Impossible de supprimer ce client. '
                . count($comptesNonVides) . ' compte(s) ont encore un solde non nul : '
                . implode(', ', $comptesNonVides)
                . '. Tous les comptes doivent être à 0 € avant la suppression.'
            );
            return $this->redirectToRoute('admin_client_show', ['id' => $client->getId()]);
        }

        // Tous les comptes sont à 0 € → suppression autorisée
        $nom = $client->getFullName();
        $em->remove($client);
        $em->flush();

        $this->addFlash('success', '🗑️ Client "' . $nom . '" supprimé avec succès.');
        return $this->redirectToRoute('admin_clients');
    }

    // ──────────────────────────────────────────
    //  SUPPRIMER UN COMPTE (par l'admin)
    //
    //  Règles :
    //  - Solde doit être exactement 0 €
    // ──────────────────────────────────────────
    #[Route('/comptes/{id}/supprimer', name: 'admin_compte_delete', methods: ['POST'])]
    public function compteDelete(CompteBancaire $compte, Request $request, EntityManagerInterface $em): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('admin_delete_compte_' . $compte->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Token de sécurité invalide.');
            return $this->redirectToRoute('admin_client_show', ['id' => $compte->getUser()->getId()]);
        }

        $solde = round((float) $compte->getSolde(), 2);

        // Solde > 0 → suppression bloquée
        if ($solde > 0) {
            $this->addFlash(
                'error',
                sprintf(
                    '❌ Impossible de supprimer ce compte : solde de %.2f €. Le solde doit être à 0 € avant la suppression.',
                    $solde
                )
            );
            return $this->redirectToRoute('admin_client_show', ['id' => $compte->getUser()->getId()]);
        }

        // Solde < 0 → suppression bloquée
        if ($solde < 0) {
            $this->addFlash('error', '❌ Ce compte a un solde négatif. Veuillez régulariser avant de supprimer.');
            return $this->redirectToRoute('admin_client_show', ['id' => $compte->getUser()->getId()]);
        }

        // Solde = 0 → suppression autorisée
        $clientId = $compte->getUser()->getId();
        $label    = $compte->getTypeLabel() . ' (' . $compte->getIban() . ')';

        $em->remove($compte);
        $em->flush();

        $this->addFlash('success', '🗑️ Compte "' . $label . '" supprimé avec succès.');
        return $this->redirectToRoute('admin_client_show', ['id' => $clientId]);
    }

    // ──────────────────────────────────────────
    //  LISTE COMPTES
    // ──────────────────────────────────────────
    #[Route('/comptes', name: 'admin_comptes')]
    public function comptes(CompteBancaireRepository $compteRepo): Response
    {
        return $this->render('admin/comptes.html.twig', [
            'comptes' => $compteRepo->findAll(),
        ]);
    }

    // ──────────────────────────────────────────
    //  BLOQUER / DÉBLOQUER UN COMPTE
    // ──────────────────────────────────────────
    #[Route('/comptes/{id}/toggle', name: 'admin_compte_toggle', methods: ['POST'])]
    public function toggleCompte(CompteBancaire $compte, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('toggle_compte_' . $compte->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', '❌ Token de sécurité invalide.');
            return $this->redirectToRoute('admin_comptes');
        }

        if ($compte->isActif()) {
            $compte->setStatut(CompteBancaire::STATUS_BLOQUE);
            $this->addFlash('warning', '🔒 Compte bloqué avec succès.');
        } else {
            $compte->setStatut(CompteBancaire::STATUS_ACTIF);
            $this->addFlash('success', '🔓 Compte débloqué avec succès.');
        }

        $em->flush();

        $referer = $request->headers->get('referer');
        return $referer
            ? $this->redirect($referer)
            : $this->redirectToRoute('admin_comptes');
    }

    // ──────────────────────────────────────────
    //  TRANSACTIONS D'UN COMPTE
    // ──────────────────────────────────────────
    #[Route('/comptes/{id}/transactions', name: 'admin_compte_transactions')]
    public function compteTransactions(CompteBancaire $compte): Response
    {
        return $this->render('admin/compte_transactions.html.twig', [
            'compte'       => $compte,
            'transactions' => $compte->getAllTransactions(),
        ]);
    }
}
