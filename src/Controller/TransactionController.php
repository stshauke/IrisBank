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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/transactions')]
class TransactionController extends AbstractController
{
    #[Route('/', name: 'app_transactions_history', methods: ['GET'])]
    public function history(TransactionRepository $transactionRepo): Response
    {
        $user = $this->getUser();
        $transactions = $transactionRepo->findByUser($user);

        return $this->render('transaction/history.html.twig', [
            'transactions' => $transactions,
        ]);
    }

    #[Route('/depot/{id}', name: 'app_depot', methods: ['GET', 'POST'])]
    public function depot(CompteBancaire $compte, Request $request, BanqueService $banqueService): Response
    {
        $this->checkOwnership($compte);

        $form = $this->createForm(DepotType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var array{montant:mixed, libelle:?string} $data */
                $data = $form->getData();

                $banqueService->depot(
                    $compte,
                    (string) $data['montant'],
                    $data['libelle'] ?? null
                );

                $this->addFlash(
                    'success',
                    sprintf('✅ Dépôt de %.2f€ effectué avec succès !', (float) $data['montant'])
                );

                return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('transaction/depot.html.twig', [
            'form' => $form,
            'compte' => $compte,
        ]);
    }

    #[Route('/retrait/{id}', name: 'app_retrait', methods: ['GET', 'POST'])]
    public function retrait(CompteBancaire $compte, Request $request, BanqueService $banqueService): Response
    {
        $this->checkOwnership($compte);

        $form = $this->createForm(RetraitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var array{montant:mixed, libelle:?string} $data */
                $data = $form->getData();

                $banqueService->retrait(
                    $compte,
                    (string) $data['montant'],
                    $data['libelle'] ?? null
                );

                $this->addFlash(
                    'success',
                    sprintf('✅ Retrait de %.2f€ effectué avec succès !', (float) $data['montant'])
                );

                return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('transaction/retrait.html.twig', [
            'form' => $form,
            'compte' => $compte,
        ]);
    }

    #[Route('/virement', name: 'app_virement', methods: ['GET', 'POST'])]
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
                /** @var array{
                 *     compteSource: CompteBancaire,
                 *     compteDestinataire: ?CompteBancaire,
                 *     ibanDestinataire: ?string,
                 *     montant: mixed,
                 *     libelle: ?string
                 * } $data
                 */
                $data = $form->getData();

                $source = $data['compteSource'];
                $montant = (string) $data['montant'];
                $libelle = $data['libelle'] ?? null;
                $ibanDestinataire = trim((string) ($data['ibanDestinataire'] ?? ''));
                $compteDestinataire = $data['compteDestinataire'] ?? null;

                if ($source->getUser() !== $user) {
                    throw $this->createAccessDeniedException();
                }

                if ($ibanDestinataire !== '' && $compteDestinataire !== null) {
                    throw new BadRequestHttpException('Choisissez soit un compte destinataire, soit un IBAN, mais pas les deux.');
                }

                if ($ibanDestinataire === '' && $compteDestinataire === null) {
                    throw new BadRequestHttpException('Veuillez sélectionner un compte destinataire ou saisir un IBAN.');
                }

                if ($ibanDestinataire !== '') {
                    $destinataire = $compteRepo->findOneBy(['iban' => $ibanDestinataire]);

                    if (!$destinataire instanceof CompteBancaire) {
                        throw new BadRequestHttpException('Aucun compte trouvé avec cet IBAN.');
                    }
                } else {
                    $destinataire = $compteDestinataire;

                    if (!$destinataire instanceof CompteBancaire) {
                        throw new BadRequestHttpException('Le compte destinataire est invalide.');
                    }
                }

                $banqueService->virement($source, $destinataire, $montant, $libelle);

                $this->addFlash(
                    'success',
                    sprintf('✅ Virement de %.2f€ effectué avec succès !', (float) $montant)
                );

                return $this->redirectToRoute('app_transactions_history');
            } catch (\Throwable $e) {
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