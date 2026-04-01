<?php

namespace App\Command;

use App\Entity\CarteVirtuelle;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:simulate-carte-transactions',
    description: 'Simule des transactions sur toutes les cartes virtuelles actives',
)]
class SimulateCarteTransactionsCommand extends Command
{
    private const SCENARIOS = [
        ['libelle' => 'Paiement Amazon',         'montant' => '49.99',  'type' => Transaction::TYPE_RETRAIT],
        ['libelle' => 'Abonnement Netflix',       'montant' => '15.99',  'type' => Transaction::TYPE_RETRAIT],
        ['libelle' => 'Uber Eats',                'montant' => '22.50',  'type' => Transaction::TYPE_RETRAIT],
        ['libelle' => 'Remboursement boutique',   'montant' => '12.00',  'type' => Transaction::TYPE_DEPOT],
        ['libelle' => 'Achat Fnac.com',           'montant' => '89.00',  'type' => Transaction::TYPE_RETRAIT],
        ['libelle' => 'Steam — jeu vidéo',        'montant' => '29.99',  'type' => Transaction::TYPE_RETRAIT],
        ['libelle' => 'Apple iCloud',             'montant' => '0.99',   'type' => Transaction::TYPE_RETRAIT],
        ['libelle' => 'Cashback Mastercard',      'montant' => '5.00',   'type' => Transaction::TYPE_DEPOT],
        ['libelle' => 'Booking.com — hôtel',      'montant' => '134.00', 'type' => Transaction::TYPE_RETRAIT],
        ['libelle' => 'Spotify Premium',          'montant' => '9.99',   'type' => Transaction::TYPE_RETRAIT],
    ];

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cartes = $this->em->getRepository(CarteVirtuelle::class)->findBy(['statut' => CarteVirtuelle::STATUT_ACTIVE]);

        if (empty($cartes)) {
            $io->warning('Aucune carte virtuelle active trouvée.');
            return Command::SUCCESS;
        }

        foreach ($cartes as $carte) {
            $compte = $carte->getCompte();
            $scenarios = self::SCENARIOS;
            shuffle($scenarios);
            $pick = array_slice($scenarios, 0, rand(3, 5));

            foreach ($pick as $i => $scenario) {
                $tx = new Transaction();
                $tx->setType($scenario['type']);
                $tx->setMontant($scenario['montant']);
                $tx->setLibelle($scenario['libelle']);
                $tx->setCarteVirtuelle($carte);

                if ($scenario['type'] === Transaction::TYPE_RETRAIT) {
                    $tx->setCompteSource($compte);
                } else {
                    $tx->setCompteDestinataire($compte);
                }

                // Étaler les dates sur les 30 derniers jours
                $daysAgo = rand(0, 30);
                $minutesAgo = rand(0, 1440);
                $date = new \DateTimeImmutable("-{$daysAgo} days -{$minutesAgo} minutes");
                $tx->setCreatedAt($date);

                $this->em->persist($tx);
            }

            $io->text(sprintf('✅ %d transactions simulées sur carte %s (%s)',
                count($pick),
                $carte->getNumeroMasque(),
                strtoupper($carte->getType())
            ));
        }

        $this->em->flush();
        $io->success(sprintf('%d cartes traitées.', count($cartes)));

        return Command::SUCCESS;
    }
}
