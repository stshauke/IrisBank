<?php
// src/Form/RegistrationFormType.php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom', 'attr' => ['placeholder' => 'Dupont']])
            ->add('prenom', TextType::class, ['label' => 'Prénom', 'attr' => ['placeholder' => 'Jean']])
            ->add('email', EmailType::class, ['label' => 'Email', 'attr' => ['placeholder' => 'jean@exemple.fr']])
            ->add('telephone', TelType::class, ['label' => 'Téléphone', 'required' => false, 'attr' => ['placeholder' => '0612345678']])
            ->add('adresse', TextType::class, ['label' => 'Adresse', 'required' => false])
            ->add('dateNaissance', BirthdayType::class, ['label' => 'Date de naissance', 'required' => false, 'widget' => 'single_text'])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Mot de passe', 'attr' => ['placeholder' => 'Min. 8 caractères']],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'constraints' => [
                    new NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                    new Length(['min' => 8, 'minMessage' => 'Minimum {{ limit }} caractères.']),
                    new Regex([
                        'pattern' => '/^(?=.*[A-Z])(?=.*\d).{8,}$/',
                        'message' => 'Le mot de passe doit contenir au moins 1 majuscule et 1 chiffre.'
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
