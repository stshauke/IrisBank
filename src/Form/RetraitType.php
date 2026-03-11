<?php
// src/Form/RetraitType.php
namespace App\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class RetraitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant à retirer',
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un montant.']),
                    new GreaterThanOrEqual(['value' => 1, 'message' => 'Le montant minimum est 1€.']),
                    new LessThanOrEqual(['value' => 1000, 'message' => 'Le montant maximum est 1 000€.']),
                ],
                'attr' => ['placeholder' => '0.00', 'min' => '1', 'max' => '1000', 'step' => '0.01'],
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Courses, Loyer...'],
            ]);
    }
}
