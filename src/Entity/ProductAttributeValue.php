<?php

namespace App\Entity;

use App\Repository\ProductAttributeValueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Valeur concrète d'un {@see ProductAttribute} (ex. "Bleu" pour l'attribut "Couleur").
 *
 * @package App\Entity
 *
 * @property-read int|null $id Identifiant.
 * @property string|null $value
 * @property ProductAttribute|null $attribute
 */
#[ORM\Entity(repositoryClass: ProductAttributeValueRepository::class)]
class ProductAttributeValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[ORM\ManyToOne(inversedBy: 'productAttributeValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductAttribute $attribute = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getAttribute(): ?ProductAttribute
    {
        return $this->attribute;
    }

    public function setAttribute(?ProductAttribute $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Représentation lisible de la valeur, ex. "Couleur: Bleu".
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s: %s', $this->attribute?->getName(), $this->value);
    }
}
