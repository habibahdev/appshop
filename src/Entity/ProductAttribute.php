<?php

namespace App\Entity;

use App\Repository\ProductAttributeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Type d'attribut de variante (ex: "Couleur", "Taille").
 * Design EAV (Entity-Attribute-Value) : permet d'ajouter de nouveaux types
 * d'attributs sans migration de schéma.
 *
 * @package App\Entity
 *
 * @property-read int|null $id Identifiant.
 * @property string|null $name Nom de l'attribut, unique.
 * @property Collection<int, ProductAttributeValue> $productAttributeValues
 */
#[ORM\Entity(repositoryClass: ProductAttributeRepository::class)]
class ProductAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    /**
     * @var Collection<int, ProductAttributeValue>
     */
    #[ORM\OneToMany(targetEntity: ProductAttributeValue::class, mappedBy: 'attribute', cascade: ['persist', 'remove'])]
    private Collection $productAttributeValues;

    public function __construct()
    {
        $this->productAttributeValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValue>
     */
    public function getProductAttributeValues(): Collection
    {
        return $this->productAttributeValues;
    }

    public function addProductAttributeValue(ProductAttributeValue $productAttributeValue): static
    {
        if (!$this->productAttributeValues->contains($productAttributeValue)) {
            $this->productAttributeValues->add($productAttributeValue);
            $productAttributeValue->setAttribute($this);
        }

        return $this;
    }

    public function removeProductAttributeValue(ProductAttributeValue $productAttributeValue): static
    {
        if ($this->productAttributeValues->removeElement($productAttributeValue)) {
            // set the owning side to null (unless already changed)
            if ($productAttributeValue->getAttribute() === $this) {
                $productAttributeValue->setAttribute(null);
            }
        }

        return $this;
    }
}
