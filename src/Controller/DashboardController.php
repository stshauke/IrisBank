<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
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
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(TransactionRepository $transactionRepo): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $comptes = $user->getComptesBancaires();

        $soldeTotal = 0.0;
        foreach ($comptes as $compte) {
            $soldeTotal += $compte->getSoldeFloat();
        }

        $dernieresTransactions = $transactionRepo->findRecentByUser($user, 5);

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'comptes' => $comptes,
            'soldeTotal' => $soldeTotal,
            'dernieresTransactions' => $dernieresTransactions,
            'todayLabel' => (new \DateTimeImmutable())->format('d/m/Y'),
        ]);
    }
}