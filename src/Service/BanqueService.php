<?php

namespace App\Service;

use App\Entity\CompteBancaire;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BanqueService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Génère un IBAN français unique au format FR76-YBNK-XXXX-XXXX-XXXX-XXXX-XXX
     */
    public function generateIban(): string
    {
        do {
            $iban = 'FR76-YBNK-' . implode('-', [
                strtoupper(substr(md5(uniqid()), 0, 4)),
                strtoupper(substr(md5(uniqid()), 0, 4)),
                strtoupper(substr(md5(uniqid()), 0, 4)),
                strtoupper(substr(md5(uniqid()), 0, 4)),
                strtoupper(substr(md5(uniqid()), 0, 3)),
            ]);
            $existing = $this->em->getRepository(CompteBancaire::class)->findOneBy(['iban' => $iban]);
        } while ($existing !== null);

        return $iban;
    }

    /**
     * Crée un nouveau compte bancaire
     */
    public function creerCompte(User $user, string $type): CompteBancaire
    {
        $compte = new CompteBancaire();
        $compte->setUser($user);
        $compte->setIban($this->generateIban());
        $compte->setType($type);
        $compte->setSolde('0.00');
        $compte->setStatut(CompteBancaire::STATUS_ACTIF);

        $this->em->persist($compte);
        $this->em->flush();

        return $compte;
    }

    /**
     * Effectue un dépôt
     */
    public function depot(CompteBancaire $compte, float $montant, ?string $libelle = null): Transaction
    {
        $this->validateMontant($montant);
        $this->validateCompteActif($compte);

        if ($montant < 1) {
            throw new BadRequestHttpException('Le montant minimum de dépôt est 1€.');
        }

        $nouveauSolde = $compte->getSoldeFloat() + $montant;
        $compte->setSolde(number_format($nouveauSolde, 2, '.', ''));

        $transaction = new Transaction();
        $transaction->setCompteDestinataire($compte);
        $transaction->setType(Transaction::TYPE_DEPOT);
        $transaction->setMontant(number_format($montant, 2, '.', ''));
        $transaction->setLibelle($libelle ?? 'Dépôt');

        $this->em->persist($transaction);
        $this->em->flush();

        return $transaction;
    }

    /**
     * Effectue un retrait
     */
    public function retrait(CompteBancaire $compte, float $montant, ?string $libelle = null): Transaction
    {
        $this->validateMontant($montant);
        $this->validateCompteActif($compte);

        if ($montant < 1) {
            throw new BadRequestHttpException('Le montant minimum de retrait est 1€.');
        }
        if ($montant > 1000) {
            throw new BadRequestHttpException('Le montant maximum par retrait est 1 000€.');
        }
        if ($compte->getSoldeFloat() < $montant) {
            throw new BadRequestHttpException('Solde insuffisant pour effectuer ce retrait.');
        }

        $nouveauSolde = $compte->getSoldeFloat() - $montant;
        $compte->setSolde(number_format($nouveauSolde, 2, '.', ''));

        $transaction = new Transaction();
        $transaction->setCompteSource($compte);
        $transaction->setType(Transaction::TYPE_RETRAIT);
        $transaction->setMontant(number_format($montant, 2, '.', ''));
        $transaction->setLibelle($libelle ?? 'Retrait');

        $this->em->persist($transaction);
        $this->em->flush();

        return $transaction;
    }

    /**
     * Effectue un virement entre deux comptes
     */
    public function virement(CompteBancaire $source, CompteBancaire $destinataire, float $montant, ?string $libelle = null): array
    {
        $this->validateMontant($montant);
        $this->validateCompteActif($source);
        $this->validateCompteActif($destinataire);

        if ($source->getId() === $destinataire->getId()) {
            throw new BadRequestHttpException('Impossible de virer vers le même compte.');
        }
        if ($montant < 1) {
            throw new BadRequestHttpException('Le montant minimum de virement est 1€.');
        }
        if ($source->getSoldeFloat() < $montant) {
            throw new BadRequestHttpException('Solde insuffisant pour effectuer ce virement.');
        }

        // Mise à jour des soldes
        $source->setSolde(number_format($source->getSoldeFloat() - $montant, 2, '.', ''));
        $destinataire->setSolde(number_format($destinataire->getSoldeFloat() + $montant, 2, '.', ''));

        $lib = $libelle ?? ('Virement vers ' . $destinataire->getIban());

        // Transaction émise
        $txEmis = new Transaction();
        $txEmis->setCompteSource($source);
        $txEmis->setCompteDestinataire($destinataire);
        $txEmis->setType(Transaction::TYPE_VIREMENT_EMIS);
        $txEmis->setMontant(number_format($montant, 2, '.', ''));
        $txEmis->setLibelle($lib);

        // Transaction reçue
        $txRecu = new Transaction();
        $txRecu->setCompteSource($source);
        $txRecu->setCompteDestinataire($destinataire);
        $txRecu->setType(Transaction::TYPE_VIREMENT_RECU);
        $txRecu->setMontant(number_format($montant, 2, '.', ''));
        $txRecu->setLibelle('Virement reçu de ' . $source->getIban());

        $this->em->persist($txEmis);
        $this->em->persist($txRecu);
        $this->em->flush();

        return [$txEmis, $txRecu];
    }

    /**
     * Supprime un compte si solde = 0
     */
    public function supprimerCompte(CompteBancaire $compte): void
    {
        if ($compte->getSoldeFloat() !== 0.0) {
            throw new BadRequestHttpException('Le compte ne peut être supprimé que si son solde est de 0€.');
        }

        $this->em->remove($compte);
        $this->em->flush();
    }

    private function validateMontant(float $montant): void
    {
        if ($montant <= 0) {
            throw new BadRequestHttpException('Le montant doit être positif.');
        }
        // Max 2 décimales
        if (round($montant, 2) !== $montant) {
            throw new BadRequestHttpException('Le montant ne peut avoir plus de 2 décimales.');
        }
    }

    private function validateCompteActif(CompteBancaire $compte): void
    {
        if ($compte->isBloque()) {
            throw new BadRequestHttpException('Ce compte est bloqué et ne peut pas effectuer de transactions.');
        }
    }
}
