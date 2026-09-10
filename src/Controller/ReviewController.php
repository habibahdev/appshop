<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ReviewService;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ReviewController extends AbstractController
{
    #[Route('/product/{slug}/review', name: 'app_review_create', methods: ['POST'])]
    public function create(
        string $slug,
        ProductRepository $productRepository,
        Request $request,
        ReviewService $reviewService
    ): Response {
        $product = $productRepository->findOneBy(['slug' => $slug, 'isActive' => true])
            ?? throw $this->createNotFoundException();
            /** @var User $user */
        $user = $this->getUser();
        try {
            $reviewService->create(
                $product,
                $user,
                (int) $request->request->get('rating', 5),
                trim((string) $request->request->get('comment', ''))
            );
            $this->addFlash('success', 'Merci, ton avis a été soumis');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_product_show', [
            'slug' => $product->getSlug()
        ]);
    }
}
