<?php
// src/Form/ProfilType.php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('prenom', TextType::class, ['label' => 'Prénom'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('telephone', TelType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('adresse', TextType::class, ['label' => 'Adresse', 'required' => false])
            ->add('dateNaissance', BirthdayType::class, ['label' => 'Date de naissance', 'required' => false, 'widget' => 'single_text']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
