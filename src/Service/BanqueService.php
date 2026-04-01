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
     * Génère un IBAN démo unique.
     */
    public function generateIban(): string
    {
        do {
            $iban = 'FR76-YBNK-' . implode('-', [
                strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)),
                strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)),
                strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)),
                strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)),
                strtoupper(substr(bin2hex(random_bytes(2)), 0, 3)),
            ]);

            $existing = $this->em
                ->getRepository(CompteBancaire::class)
                ->findOneBy(['iban' => $iban]);
        } while ($existing !== null);

        return $iban;
    }

    /**
     * Crée un nouveau compte bancaire.
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
     * Effectue un dépôt.
     */
    public function depot(CompteBancaire $compte, float|string $montant, ?string $libelle = null): Transaction
    {
        $montant = $this->normalizeAmount($montant);
        $this->validateMontant($montant);
        $this->validateCompteActif($compte);

        if ($this->compareAmounts($montant, '1.00') < 0) {
            throw new BadRequestHttpException('Le montant minimum de dépôt est 1€.');
        }

        return $this->em->wrapInTransaction(function () use ($compte, $montant, $libelle) {
            $nouveauSolde = $this->addAmounts($compte->getSolde(), $montant);
            $compte->setSolde($nouveauSolde);

            $transaction = new Transaction();
            $transaction->setCompteDestinataire($compte);
            $transaction->setType(Transaction::TYPE_DEPOT);
            $transaction->setMontant($montant);
            $transaction->setLibelle($libelle ?: 'Dépôt');

            $this->em->persist($transaction);
            $this->em->flush();

            return $transaction;
        });
    }

    /**
     * Effectue un retrait.
     */
    public function retrait(CompteBancaire $compte, float|string $montant, ?string $libelle = null): Transaction
    {
        $montant = $this->normalizeAmount($montant);
        $this->validateMontant($montant);
        $this->validateCompteActif($compte);

        if ($this->compareAmounts($montant, '1.00') < 0) {
            throw new BadRequestHttpException('Le montant minimum de retrait est 1€.');
        }

        if ($this->compareAmounts($montant, '1000.00') > 0) {
            throw new BadRequestHttpException('Le montant maximum par retrait est 1 000€.');
        }

        if ($this->compareAmounts($compte->getSolde(), $montant) < 0) {
            throw new BadRequestHttpException('Solde insuffisant pour effectuer ce retrait.');
        }

        return $this->em->wrapInTransaction(function () use ($compte, $montant, $libelle) {
            $nouveauSolde = $this->subtractAmounts($compte->getSolde(), $montant);
            $compte->setSolde($nouveauSolde);

            $transaction = new Transaction();
            $transaction->setCompteSource($compte);
            $transaction->setType(Transaction::TYPE_RETRAIT);
            $transaction->setMontant($montant);
            $transaction->setLibelle($libelle ?: 'Retrait');

            $this->em->persist($transaction);
            $this->em->flush();

            return $transaction;
        });
    }

    /**
     * Effectue un virement entre deux comptes.
     *
     * @return array{0: Transaction, 1: Transaction}
     */
    public function virement(
        CompteBancaire $source,
        CompteBancaire $destinataire,
        float|string $montant,
        ?string $libelle = null
    ): array {
        $montant = $this->normalizeAmount($montant);
        $this->validateMontant($montant);
        $this->validateCompteActif($source);
        $this->validateCompteActif($destinataire);

        if ($source->getId() === $destinataire->getId()) {
            throw new BadRequestHttpException('Impossible de virer vers le même compte.');
        }

        if ($this->compareAmounts($montant, '1.00') < 0) {
            throw new BadRequestHttpException('Le montant minimum de virement est 1€.');
        }

        if ($this->compareAmounts($source->getSolde(), $montant) < 0) {
            throw new BadRequestHttpException('Solde insuffisant pour effectuer ce virement.');
        }

        return $this->em->wrapInTransaction(function () use ($source, $destinataire, $montant, $libelle) {
            $source->setSolde($this->subtractAmounts($source->getSolde(), $montant));
            $destinataire->setSolde($this->addAmounts($destinataire->getSolde(), $montant));

            $libelleEmis = $libelle ?: ('Virement vers ' . $destinataire->getIban());

            $txEmis = new Transaction();
            $txEmis->setCompteSource($source);
            $txEmis->setCompteDestinataire($destinataire);
            $txEmis->setType(Transaction::TYPE_VIREMENT_EMIS);
            $txEmis->setMontant($montant);
            $txEmis->setLibelle($libelleEmis);

            $txRecu = new Transaction();
            $txRecu->setCompteSource($source);
            $txRecu->setCompteDestinataire($destinataire);
            $txRecu->setType(Transaction::TYPE_VIREMENT_RECU);
            $txRecu->setMontant($montant);
            $txRecu->setLibelle('Virement reçu de ' . $source->getIban());

            $this->em->persist($txEmis);
            $this->em->persist($txRecu);
            $this->em->flush();

            return [$txEmis, $txRecu];
        });
    }

    /**
     * Supprime un compte si solde = 0.
     */
    public function supprimerCompte(CompteBancaire $compte): void
    {
        if ($this->compareAmounts($compte->getSolde(), '0.00') !== 0) {
            throw new BadRequestHttpException('Le compte ne peut être supprimé que si son solde est de 0€.');
        }

        $this->em->remove($compte);
        $this->em->flush();
    }

    private function validateMontant(string $montant): void
    {
        if (!preg_match('/^\d+\.\d{2}$/', $montant)) {
            throw new BadRequestHttpException('Le montant doit avoir exactement 2 décimales.');
        }

        if ($this->compareAmounts($montant, '0.00') <= 0) {
            throw new BadRequestHttpException('Le montant doit être positif.');
        }
    }

    private function validateCompteActif(CompteBancaire $compte): void
    {
        if ($compte->isBloque()) {
            throw new BadRequestHttpException('Ce compte est bloqué et ne peut pas effectuer de transactions.');
        }
    }

    private function normalizeAmount(float|string $montant): string
    {
        if (is_float($montant) || is_int($montant)) {
            return number_format((float) $montant, 2, '.', '');
        }

        $montant = trim(str_replace(',', '.', $montant));

        if ($montant === '') {
            throw new BadRequestHttpException('Le montant est obligatoire.');
        }

        if (!is_numeric($montant)) {
            throw new BadRequestHttpException('Le montant doit être un nombre valide.');
        }

        return number_format((float) $montant, 2, '.', '');
    }

    private function addAmounts(string $left, string $right): string
    {
        return number_format((float) $left + (float) $right, 2, '.', '');
    }

    private function subtractAmounts(string $left, string $right): string
    {
        return number_format((float) $left - (float) $right, 2, '.', '');
    }

    private function compareAmounts(string $left, string $right): int
    {
        return ((float) $left <=> (float) $right);
    }
}