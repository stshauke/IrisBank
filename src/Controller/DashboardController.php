<?php

namespace App\Controller;

use App\Entity\CompteBancaire;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(TransactionRepository $transactionRepo): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $comptes = $user->getComptesBancaires();

        // Calcul du solde total
        $soldeTotal = 0;
        foreach ($comptes as $compte) {
            $soldeTotal += $compte->getSoldeFloat();
        }

        // Dernières transactions (5)
        $dernieresTransactions = $transactionRepo->findRecentByUser($user, 5);

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'comptes' => $comptes,
            'soldeTotal' => $soldeTotal,
            'dernieresTransactions' => $dernieresTransactions,
        ]);
    }
}
