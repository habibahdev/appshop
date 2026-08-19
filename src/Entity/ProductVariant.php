<?php

namespace App\Entity;

use App\Repository\ProductVariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductVariantRepository::class)]
class ProductVariant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sku = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    /**
     * @var Collection<int, ProductAttributeValue>
     */
    #[ORM\ManyToMany(targetEntity: ProductAttributeValue::class)]
    #[ORM\JoinTable(name: 'variant_attribute_value')]
    private Collection $attributeValues;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function __construct()
    {
        $this->attributeValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, ProductAttributeValue>
     */
    public function getAttributeValues(): Collection
    {
        return $this->attributeValues;
    }

    public function addAttributeValue(ProductAttributeValue $attributeValue): static
    {
        if (!$this->attributeValues->contains($attributeValue)) {
            $this->attributeValues->add($attributeValue);
        }

        return $this;
    }

    public function removeAttributeValue(ProductAttributeValue $attributeValue): static
    {
        $this->attributeValues->removeElement($attributeValue);

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getLabel(): string
    {
        return implode(', ', array_map(fn ($v) => (string) $v, $this->attributeValues->toArray()));
    }
}
