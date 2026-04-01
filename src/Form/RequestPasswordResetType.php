<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class RequestPasswordResetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label'       => 'Adresse email',
            'constraints' => [
                new NotBlank(['message' => "L'email est obligatoire."]),
                new Email(['message' => "L'email n'est pas valide."]),
            ],
            'attr' => ['placeholder' => 'votre@email.fr'],
        ]);
    }
}
