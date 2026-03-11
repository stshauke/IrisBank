<?php

namespace App\Controller;

use App\Entity\CompteBancaire;
use App\Form\DepotType;
use App\Form\RetraitType;
use App\Form\VirementType;
use App\Repository\CompteBancaireRepository;
use App\Repository\TransactionRepository;
use App\Service\BanqueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/transactions')]
class TransactionController extends AbstractController
{
    #[Route('/', name: 'app_transactions_history')]
    public function history(TransactionRepository $transactionRepo): Response
    {
        $user = $this->getUser();
        $transactions = $transactionRepo->findByUser($user);

        return $this->render('transaction/history.html.twig', [
            'transactions' => $transactions,
        ]);
    }

    #[Route('/depot/{id}', name: 'app_depot')]
    public function depot(CompteBancaire $compte, Request $request, BanqueService $banqueService): Response
    {
        $this->checkOwnership($compte);

        $form = $this->createForm(DepotType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                $banqueService->depot($compte, (float)$data['montant'], $data['libelle'] ?? null);
                $this->addFlash('success', sprintf('✅ Dépôt de %.2f€ effectué avec succès !', $data['montant']));
                return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('transaction/depot.html.twig', [
            'form' => $form,
            'compte' => $compte,
        ]);
    }

    #[Route('/retrait/{id}', name: 'app_retrait')]
    public function retrait(CompteBancaire $compte, Request $request, BanqueService $banqueService): Response
    {
        $this->checkOwnership($compte);

        $form = $this->createForm(RetraitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                $banqueService->retrait($compte, (float)$data['montant'], $data['libelle'] ?? null);
                $this->addFlash('success', sprintf('✅ Retrait de %.2f€ effectué avec succès !', $data['montant']));
                return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('transaction/retrait.html.twig', [
            'form' => $form,
            'compte' => $compte,
        ]);
    }

    #[Route('/virement', name: 'app_virement')]
    public function virement(
        Request $request,
        BanqueService $banqueService,
        CompteBancaireRepository $compteRepo
    ): Response {
        $user = $this->getUser();
        $mesComptes = $user->getComptesBancaires()->toArray();

        $form = $this->createForm(VirementType::class, null, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                $source = $data['compteSource'];
                $montant = (float)$data['montant'];
                $libelle = $data['libelle'] ?? null;

                // Virement interne ou via IBAN
                if (!empty($data['ibanDestinataire'])) {
                    $destinataire = $compteRepo->findOneBy(['iban' => $data['ibanDestinataire']]);
                    if (!$destinataire) {
                        throw new \Exception("Aucun compte trouvé avec cet IBAN.");
                    }
                } else {
                    $destinataire = $data['compteDestinataire'];
                }

                $banqueService->virement($source, $destinataire, $montant, $libelle);
                $this->addFlash('success', sprintf('✅ Virement de %.2f€ effectué avec succès !', $montant));
                return $this->redirectToRoute('app_transactions_history');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('transaction/virement.html.twig', [
            'form' => $form,
            'mesComptes' => $mesComptes,
        ]);
    }

    private function checkOwnership(CompteBancaire $compte): void
    {
        if ($compte->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    }
}
