<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\ProductVariant;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Invalide le cache du catalogue (pool `catalog.cache`, tag-aware) dès
 * qu'un {@see Product} ou {@see ProductVariant} est créé, modifié ou supprimé.
 *
 * Écoute les événements Doctrine postPersist/postUpdate.postRemove.
 * Cible les tags `catalog` et `category{id}` correspondant à la catégorie
 * concernée, évitant qu'un produit désactivé en admin reste visible en cache
 * jusqu'à expiration naturelle.
 *
 * @package App\EventListener
 */
class ProductCacheInvalidationListener
{
    public function __construct(private TagAwareCacheInterface $cacheCatalog)
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->invalidateIfRelevant($args->getObject());
    }

    public function postUpdate(PostPersistEventArgs $args): void
    {
        $this->invalidateIfRelevant($args->getObject());
    }

    public function postRemove(PostPersistEventArgs $args): void
    {
        $this->invalidateIfRelevant($args->getObject());
    }

    private function invalidateIfRelevant(object $entity): void
    {
        if ($entity instanceof Product) {
            $this->cacheCatalog->invalidateTags([
                'catalog',
                'category' . $entity->getCategory()?->getId()
            ]);
        }

        if ($entity instanceof ProductVariant && $entity->getProduct()) {
            $this->cacheCatalog->invalidateTags([
                'catalog',
                'category' . $entity->getProduct()->getCategory()?->getId()
            ]);
        }
    }
}
