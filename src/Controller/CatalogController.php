<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[Route('/catalog', name: 'app_catalog')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $categoryId = $request->query->getInt('categorie', 0) ?: null;
        $search = $request->query->get('q');
        $sort = $request->query->get('tri', 'newest');
        $page = $request->query->getInt('page', 1);

        $result = $productRepository->findByFiltersPaginated($categoryId, $search, $sort, $page);
        $selectCategory = $categoryId ? $categoryRepository->find($categoryId) : null;

        return $this->render('catalog/index.html.twig', [
            'products' => $result['items'],
            'totalProducts' => $result['total'],
            'currentPage' => $result['page'],
            'totalPages' => $result['pages'],
            'categories' => $categoryRepository->findBy(['isActive' => true, 'parent' => null]),
            'currentCategory' => $categoryId,
            'currentSort' => $sort,
            'search' => $search,
            'selectCategory' => $selectCategory
        ]);
    }

    #[Route('/product/{slug}', name: 'app_product_show')]
    public function show(string $slug, ProductRepository $productRepository, StockRepository $stockRepository): Response
    {
        $product = $productRepository->findOneBy([
            'slug' => $slug,
            'isActive' => true
        ]) ?? throw $this->createNotFoundException();

        $colors = [];
        $matrix = [];

        $stocks = [];
        foreach ($product->getVariants() as $variant) {
            $colorValue = null;
            $sizeValue = null;

            foreach ($variant->getAttributeValues() as $attributeValue) {
                $attrName = $attributeValue->getAttribute()?->getName();
                if ($attrName === 'Couleur') {
                    $colorValue = $attributeValue->getValue();
                } elseif ($attrName === 'Taille') {
                    $sizeValue = $attributeValue->getValue();
                }
            }

            if ($colorValue === null || $sizeValue === null) {
                continue;
            }

            $stock = $stockRepository->findOneByVariant($variant);

            $colors[$colorValue] = true;
            $matrix[$colorValue][$sizeValue] = [
                'id' => $variant->getId(),
                'price' => (float) $variant->getPrice(),
                'inStock' => $stock !== null && $stock->getQty() > 0
            ];
        }

        return $this->render('catalog/show.html.twig', [
            'product' => $product,
            'colors' => array_keys($colors),
            'matrix' => $matrix
        ]);
    }
}
