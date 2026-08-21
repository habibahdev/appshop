<?php

namespace App\Controller\Profile;

use App\Entity\User;
use App\Entity\Address;
use App\Form\AddressFormType;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class AddressController extends AbstractController
{
    #[Route('/profile/addresses', name: 'app_profile_addresses', methods: ['GET', 'POST'])]
    public function index(AddressRepository $addressRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('profile/address/index.html.twig', [
            'addresses' => $addressRepository->findByUser($user)
        ]);
    }

    #[Route('/profile/address/add', name: 'app_profile_address_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $address = new Address();
        $user->addAddress($address);

        return $this->handleAddressForm($address, $request, $entityManager, isNew: true);
    }

    #[Route('/profile/address/{id}/edit', name: 'app_profile_address_edit', methods: ['GET', 'POST'])]
    public function edit(Address $address, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $address);

        return $this->handleAddressForm($address, $request, $entityManager, isNew: false);
    }

    #[Route('/profile/address/{id}/delete', name: 'app_profile_address_delete', methods: ['POST'])]
    public function delete(Address $address, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $address);

        $entityManager->remove($address);
        $entityManager->flush();

        $this->addFlash('success', 'Adresse supprimée');

        return $this->redirectToRoute('app_profile_addresses');
    }

    private function clearOtherDefaults(User $user, ?Address $except = null): void
    {
        foreach ($user->getAddresses() as $existing) {
            if ($existing !== $except && $existing->isDefault()) {
                $existing->setIsDefault(false);
            }
        }
    }

    private function handleAddressForm(
        Address $address,
        Request $request,
        EntityManagerInterface $entityManager,
        bool $isNew
    ): Response {
        $form = $this->createForm(AddressFormType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($address->isDefault()) {
                $this->clearOtherDefaults($address->getUser(), $address);
            }

            if ($isNew) {
                $entityManager->persist($address);
            }

            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Nouvelle adresse ajoutée' : 'Adresse mise à jour');

            return $this->redirectToRoute('app_profile_addresses');
        }

        return $this->render('profile/address/form.html.twig', [
            'form' => $form
        ]);
    }
}
