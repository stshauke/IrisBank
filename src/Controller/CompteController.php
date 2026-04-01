<?php
// ============================================================
// src/Controller/CompteController.php
//
// ⚠️  UN CLIENT NE PEUT PAS SUPPRIMER SES COMPTES.
//     La suppression est réservée à l'administrateur.
// ============================================================
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
    // ──────────────────────────────────────────
    //  LISTE DES COMPTES
    // ──────────────────────────────────────────
    #[Route('/', name: 'app_comptes_list')]
    public function list(): Response
    {
        return $this->render('compte/list.html.twig', [
            'comptes' => $this->getUser()->getComptesBancaires(),
        ]);
    }

    // ──────────────────────────────────────────
    //  CRÉER UN NOUVEAU COMPTE
    // ──────────────────────────────────────────
    #[Route('/nouveau', name: 'app_comptes_new')]
    public function new(Request $request, BanqueService $banqueService): Response
    {
        $form = $this->createForm(CompteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $type   = $form->get('type')->getData();
            $compte = $banqueService->creerCompte($this->getUser(), $type);
            $this->addFlash('success', '✅ Compte ' . $compte->getTypeLabel() . ' créé avec succès !');
            return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
        }

        return $this->render('compte/new.html.twig', ['form' => $form]);
    }

    // ──────────────────────────────────────────
    //  DÉTAIL D'UN COMPTE
    // ──────────────────────────────────────────
    #[Route('/{id}', name: 'app_comptes_show')]
    public function show(CompteBancaire $compte): Response
    {
        $this->checkOwnership($compte);

        return $this->render('compte/show.html.twig', [
            'compte'       => $compte,
            'transactions' => $compte->getAllTransactions(),
        ]);
    }

    // ──────────────────────────────────────────
    //  ❌ SUPPRESSION RETIRÉE CÔTÉ CLIENT
    //  La suppression d'un compte est uniquement
    //  possible par un administrateur depuis
    //  /admin/clients/{id}
    // ──────────────────────────────────────────

    // ──────────────────────────────────────────
    //  MÉTHODE PRIVÉE : vérifier le propriétaire
    // ──────────────────────────────────────────
    private function checkOwnership(CompteBancaire $compte): void
    {
        if ($compte->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce compte.');
        }
    }
}
