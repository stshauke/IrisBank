<?php

namespace App\Controller;

use App\Entity\CarteVirtuelle;
use App\Entity\CompteBancaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/comptes/{id}/cartes')]
class CarteController extends AbstractController
{
    #[Route('/{carteId}', name: 'app_carte_transactions', methods: ['GET'])]
    public function transactions(CompteBancaire $compte, int $carteId, EntityManagerInterface $em): Response
    {
        if ($compte->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $carte = $em->getRepository(CarteVirtuelle::class)->find($carteId);
        if (!$carte || $carte->getCompte() !== $compte) {
            throw $this->createNotFoundException();
        }

        return $this->render('carte/transactions.html.twig', [
            'compte' => $compte,
            'carte'  => $carte,
        ]);
    }

    #[Route('/generer', name: 'app_carte_generer', methods: ['POST'])]
    public function generer(CompteBancaire $compte, Request $request, EntityManagerInterface $em): Response
    {
        if ($compte->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('carte_generer_' . $compte->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
        }

        if ($compte->isBloque()) {
            $this->addFlash('error', 'Impossible de générer une carte sur un compte bloqué.');
            return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
        }

        $type = $request->request->get('type', CarteVirtuelle::TYPE_VISA);
        if (!in_array($type, [CarteVirtuelle::TYPE_VISA, CarteVirtuelle::TYPE_MASTERCARD])) {
            $type = CarteVirtuelle::TYPE_VISA;
        }

        $carte = new CarteVirtuelle();
        $carte->setCompte($compte);
        $carte->setType($type);
        $carte->setNumero($this->generateCardNumber());
        $carte->setCvv((string) random_int(100, 999));

        $em->persist($carte);
        $em->flush();

        $this->addFlash('success', '💳 Carte virtuelle ' . strtoupper($type) . ' générée avec succès !');

        return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
    }

    #[Route('/{carteId}/resilier', name: 'app_carte_resilier', methods: ['POST'])]
    public function resilier(CompteBancaire $compte, int $carteId, Request $request, EntityManagerInterface $em): Response
    {
        if ($compte->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('carte_resilier_' . $carteId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
        }

        $carte = $em->getRepository(CarteVirtuelle::class)->find($carteId);
        if (!$carte || $carte->getCompte() !== $compte) {
            throw $this->createNotFoundException();
        }

        $carte->setStatut(CarteVirtuelle::STATUT_RESILIEE);
        $em->flush();

        $this->addFlash('success', '🗑️ Carte résiliée.');

        return $this->redirectToRoute('app_comptes_show', ['id' => $compte->getId()]);
    }

    private function generateCardNumber(): string
    {
        $groups = [];
        for ($i = 0; $i < 4; $i++) {
            $groups[] = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }
        return implode(' ', $groups);
    }
}
