<?php
// src/Form/VirementType.php
namespace App\Form;

use App\Entity\CompteBancaire;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class VirementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];

        $builder
            ->add('compteSource', EntityType::class, [
                'class' => CompteBancaire::class,
                'label' => 'Compte source',
                'choices' => $user->getComptesBancaires()->filter(fn($c) => $c->isActif()),
                'choice_label' => fn(CompteBancaire $c) => $c->getTypeLabel() . ' — ' . number_format($c->getSoldeFloat(), 2, ',', ' ') . '€ — ' . $c->getIban(),
                'constraints' => [new NotBlank(['message' => 'Choisissez un compte source.'])],
            ])
            ->add('compteDestinataire', EntityType::class, [
                'class' => CompteBancaire::class,
                'label' => 'Compte destinataire (mes comptes)',
                'choices' => $user->getComptesBancaires()->filter(fn($c) => $c->isActif()),
                'choice_label' => fn(CompteBancaire $c) => $c->getTypeLabel() . ' — ' . $c->getIban(),
                'required' => false,
                'placeholder' => '-- Sélectionner (ou entrer IBAN ci-dessous) --',
            ])
            ->add('ibanDestinataire', TextType::class, [
                'label' => 'IBAN d\'un autre client',
                'required' => false,
                'attr' => ['placeholder' => 'FR76-YBNK-XXXX-XXXX-XXXX-XXXX-XXX'],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un montant.']),
                    new GreaterThanOrEqual(['value' => 1, 'message' => 'Montant minimum 1€.']),
                ],
                'attr' => ['placeholder' => '0.00', 'min' => '1', 'step' => '0.01'],
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Remboursement, Loyer...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['user' => null]);
        $resolver->setRequired(['user']);
    }
}
