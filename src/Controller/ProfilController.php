<?php

namespace App\Controller;

use App\Form\ProfilType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/profil')]
class ProfilController extends AbstractController
{
    #[Route('/', name: 'app_profil')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '✅ Profil mis à jour avec succès !');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/index.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_profil_password')]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                $this->addFlash('error', 'Mot de passe actuel incorrect.');
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $data['newPassword']));
                $em->flush();
                $this->addFlash('success', '🔐 Mot de passe modifié avec succès !');
                return $this->redirectToRoute('app_profil');
            }
        }

        return $this->render('profil/password.html.twig', ['form' => $form]);
    }
}
