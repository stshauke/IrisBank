<?php
// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use App\Entity\CompteBancaire;
use App\Entity\Transaction;
use App\Entity\User;
use App\Service\BanqueService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ADMIN
        $admin = new User();
        $admin->setNom('Admin');
        $admin->setPrenom('IrisBank');
        $admin->setEmail('admin@irisbank.fr');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin1234'));
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        // CLIENTS DE TEST
        $clients = [
            ['Dupont', 'Jean', 'jean.dupont@email.fr', '0612345678'],
            ['Martin', 'Sophie', 'sophie.martin@email.fr', '0698765432'],
            ['Bernard', 'Pierre', 'pierre.bernard@email.fr', '0611223344'],
        ];

        foreach ($clients as [$nom, $prenom, $email, $tel]) {
            $user = new User();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setTelephone($tel);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'Client1234'));
            $manager->persist($user);

            // Compte courant
            $compte1 = new CompteBancaire();
            $compte1->setUser($user);
            $compte1->setIban('FR76-YBNK-' . strtoupper(substr(md5($email.'c'), 0, 4)) . '-' . strtoupper(substr(md5($email.'c1'), 0, 4)) . '-' . strtoupper(substr(md5($email.'c2'), 0, 4)) . '-' . strtoupper(substr(md5($email.'c3'), 0, 4)) . '-' . strtoupper(substr(md5($email.'c4'), 0, 3)));
            $compte1->setType(CompteBancaire::TYPE_COURANT);
            $compte1->setSolde('1500.00');
            $manager->persist($compte1);

            // Transaction de test
            $tx = new Transaction();
            $tx->setCompteDestinataire($compte1);
            $tx->setType(Transaction::TYPE_DEPOT);
            $tx->setMontant('1500.00');
            $tx->setLibelle('Dépôt initial');
            $manager->persist($tx);
        }

        $manager->flush();
    }
}
