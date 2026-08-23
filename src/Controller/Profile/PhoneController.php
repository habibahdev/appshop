<?php

namespace App\Controller\Profile;

use App\Entity\User;
use App\Form\PhoneFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class PhoneController extends AbstractController
{
    #[Route('/profile/phone', name: 'app_profile_phone', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(PhoneFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Numéro de téléphone mis à jour'
            );

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/phone/index.html.twig', [
            'form' => $form
        ]);
    }
}
