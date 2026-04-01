<?php
// ============================================================
// src/Service/BanqueService.php
// ============================================================
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

    // ──────────────────────────────────────────
    //  GÉNÉRER UN IBAN UNIQUE
    //  Format : FR76-YBNK-XXXX-XXXX-XXXX-XXXX-XXX
    // ──────────────────────────────────────────
    public function generateIban(): string
    {
        do {
            $iban = sprintf(
                'FR76-YBNK-%s-%s-%s-%s-%s',
                strtoupper(substr(md5(uniqid('', true)), 0, 4)),
                strtoupper(substr(md5(uniqid('', true)), 0, 4)),
                strtoupper(substr(md5(uniqid('', true)), 0, 4)),
                strtoupper(substr(md5(uniqid('', true)), 0, 4)),
                strtoupper(substr(md5(uniqid('', true)), 0, 3))
            );
            $existing = $this->em->getRepository(CompteBancaire::class)->findOneBy(['iban' => $iban]);
        } while ($existing !== null);

        return $iban;
    }

    // ──────────────────────────────────────────
    //  CRÉER UN NOUVEAU COMPTE
    // ──────────────────────────────────────────
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

    // ──────────────────────────────────────────
    //  DÉPÔT
    // ──────────────────────────────────────────
    public function depot(CompteBancaire $compte, float $montant, ?string $libelle = null): Transaction
    {
        $this->validateMontant($montant);
        $this->validateCompteActif($compte);

        if ($montant < 1) {
            throw new BadRequestHttpException('Le montant minimum de dépôt est 1 €.');
        }

        $nouveauSolde = round($compte->getSoldeFloat() + $montant, 2);
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

    // ──────────────────────────────────────────
    //  RETRAIT
    // ──────────────────────────────────────────
    public function retrait(CompteBancaire $compte, float $montant, ?string $libelle = null): Transaction
    {
        $this->validateMontant($montant);
        $this->validateCompteActif($compte);

        if ($montant < 1) {
            throw new BadRequestHttpException('Le montant minimum de retrait est 1 €.');
        }
        if ($montant > 1000) {
            throw new BadRequestHttpException('Le montant maximum par retrait est 1 000 €.');
        }
        if ($compte->getSoldeFloat() < $montant) {
            throw new BadRequestHttpException('Solde insuffisant pour effectuer ce retrait.');
        }

        $nouveauSolde = round($compte->getSoldeFloat() - $montant, 2);
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

    // ──────────────────────────────────────────
    //  VIREMENT
    // ──────────────────────────────────────────
    public function virement(
        CompteBancaire $source,
        CompteBancaire $destinataire,
        float          $montant,
        ?string        $libelle = null
    ): array {
        $this->validateMontant($montant);
        $this->validateCompteActif($source);
        $this->validateCompteActif($destinataire);

        if ($source->getId() === $destinataire->getId()) {
            throw new BadRequestHttpException('Impossible d\'effectuer un virement vers le même compte.');
        }
        if ($montant < 1) {
            throw new BadRequestHttpException('Le montant minimum de virement est 1 €.');
        }
        if ($source->getSoldeFloat() < $montant) {
            throw new BadRequestHttpException('Solde insuffisant pour effectuer ce virement.');
        }

        // Mise à jour des soldes avec round() pour éviter les problèmes de précision
        $source->setSolde(number_format(round($source->getSoldeFloat() - $montant, 2), 2, '.', ''));
        $destinataire->setSolde(number_format(round($destinataire->getSoldeFloat() + $montant, 2), 2, '.', ''));

        $lib = $libelle ?? ('Virement vers ' . $destinataire->getIban());

        // Transaction émise (débit du compte source)
        $txEmis = new Transaction();
        $txEmis->setCompteSource($source);
        $txEmis->setCompteDestinataire($destinataire);
        $txEmis->setType(Transaction::TYPE_VIREMENT_EMIS);
        $txEmis->setMontant(number_format($montant, 2, '.', ''));
        $txEmis->setLibelle($lib);

        // Transaction reçue (crédit du compte destinataire)
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

    // ──────────────────────────────────────────
    //  SUPPRIMER UN COMPTE
    //  ⚠️  Interdit si solde ≠ 0
    // ──────────────────────────────────────────
    public function supprimerCompte(CompteBancaire $compte): void
    {
        // On utilise round() pour éviter les problèmes de précision float
        // Ex : 0.1 + 0.2 en PHP donne 0.30000000000000004
        $solde = round((float) $compte->getSolde(), 2);

        if ($solde !== 0.0) {
            throw new BadRequestHttpException(
                sprintf(
                    'Impossible de supprimer ce compte : le solde est de %.2f €. Il doit être exactement 0 €.',
                    $solde
                )
            );
        }

        $this->em->remove($compte);
        $this->em->flush();
    }

    // ──────────────────────────────────────────
    //  MÉTHODES PRIVÉES DE VALIDATION
    // ──────────────────────────────────────────

    private function validateMontant(float $montant): void
    {
        if ($montant <= 0) {
            throw new BadRequestHttpException('Le montant doit être positif.');
        }
        // Vérifie max 2 décimales
        if (round($montant, 2) !== $montant) {
            throw new BadRequestHttpException('Le montant ne peut pas avoir plus de 2 décimales.');
        }
    }

    private function validateCompteActif(CompteBancaire $compte): void
    {
        if ($compte->isBloque()) {
            throw new BadRequestHttpException(
                'Le compte ' . $compte->getIban() . ' est bloqué. Aucune transaction n\'est possible.'
            );
        }
    }
}
