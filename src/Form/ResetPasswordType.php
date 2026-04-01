<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('newPassword', RepeatedType::class, [
            'type'          => PasswordType::class,
            'mapped'        => false,
            'first_options' => ['label' => 'Nouveau mot de passe'],
            'second_options' => ['label' => 'Confirmer le mot de passe'],
            'constraints'   => [
                new NotBlank(),
                new Length(['min' => 8]),
                new Regex([
                    'pattern' => '/^(?=.*[A-Z])(?=.*\d).{8,}$/',
                    'message' => 'Min. 1 majuscule, 1 chiffre, 8 caractères.',
                ]),
            ],
        ]);
    }
}
