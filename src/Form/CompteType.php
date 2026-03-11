<?php
// src/Form/CompteType.php
namespace App\Form;

use App\Entity\CompteBancaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('type', ChoiceType::class, [
            'label' => 'Type de compte',
            'choices' => [
                'Compte Courant' => CompteBancaire::TYPE_COURANT,
                'Livret A' => CompteBancaire::TYPE_LIVRET_A,
                'PEL' => CompteBancaire::TYPE_PEL,
            ],
            'expanded' => true,
        ]);
    }
}
