<?php

namespace App\Controller\Profile;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ProfileChangePasswordFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordController extends AbstractController
{
    #[Route('/profile/password', name: 'app_profile_password', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $hasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileChangePasswordFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $curPassword = $form->get('currentPassword')->getData();

            if (!$hasher->isPasswordValid($user, $curPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect');

                return $this->render('profile/password/index.html.twig', [
                    'form' => $form
                ]);
            }

            $newPassword = $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $newPassword));

            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe mis à jour');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/password/index.html.twig', [
            'form' => $form
        ]);
    }
}
