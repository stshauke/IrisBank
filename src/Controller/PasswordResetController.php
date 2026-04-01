<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Form\RequestPasswordResetType;
use App\Form\ResetPasswordType;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_password_reset_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        UserRepository $userRepository,
        PasswordResetTokenRepository $tokenRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $form = $this->createForm(RequestPasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailAddress = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $emailAddress]);

            if ($user) {
                // Supprimer les anciens tokens de cet utilisateur
                foreach ($tokenRepository->findBy(['user' => $user]) as $old) {
                    $em->remove($old);
                }

                $token     = bin2hex(random_bytes(32));
                $expiresAt = new \DateTimeImmutable('+1 hour');
                $resetToken = new PasswordResetToken($user, $token, $expiresAt);
                $em->persist($resetToken);
                $em->flush();

                $resetUrl = $this->generateUrl(
                    'app_password_reset',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $email = (new TemplatedEmail())
                    ->from('noreply@irisbank.fr')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe IrisBank')
                    ->htmlTemplate('emails/reset_password.html.twig')
                    ->context([
                        'user'      => $user,
                        'resetUrl'  => $resetUrl,
                        'expiresAt' => $expiresAt,
                    ]);

                $mailer->send($email);
            }

            // Toujours afficher un message générique (anti-énumération)
            $this->addFlash('success', 'Si un compte existe avec cet email, un lien de réinitialisation vous a été envoyé. Vérifiez votre boîte mail.');
            return $this->redirectToRoute('app_password_reset_request');
        }

        return $this->render('security/reset_request.html.twig', ['form' => $form]);
    }

    #[Route('/mot-de-passe-oublie/{token}', name: 'app_password_reset', methods: ['GET', 'POST'])]
    public function reset(
        string $token,
        Request $request,
        PasswordResetTokenRepository $tokenRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $resetToken = $tokenRepository->findValidToken($token);

        if (!$resetToken) {
            $this->addFlash('error', 'Ce lien est invalide ou a expiré. Faites une nouvelle demande.');
            return $this->redirectToRoute('app_password_reset_request');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $user = $resetToken->getUser();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->remove($resetToken);
            $em->flush();

            $this->addFlash('success', '🔐 Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form'  => $form,
            'token' => $token,
        ]);
    }
}
