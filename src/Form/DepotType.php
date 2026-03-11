<?php
// src/Form/DepotType.php
namespace App\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class DepotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant à déposer',
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un montant.']),
                    new GreaterThanOrEqual(['value' => 1, 'message' => 'Le montant minimum est 1€.']),
                ],
                'attr' => ['placeholder' => '0.00', 'min' => '1', 'step' => '0.01'],
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Salaire, Remboursement...'],
            ]);
    }
}
