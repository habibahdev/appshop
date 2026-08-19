<?php

namespace App\Entity;

use App\Repository\DetailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetailRepository::class)]
class Detail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'details')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Purchase $purchase = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column(length: 255)]
    private ?string $variantLabel = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $productPrice = null;

    #[ORM\Column]
    private ?int $qty = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductVariant $variant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPurchase(): ?Purchase
    {
        return $this->purchase;
    }

    public function setPurchase(?Purchase $purchase): static
    {
        $this->purchase = $purchase;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getVariantLabel(): ?string
    {
        return $this->variantLabel;
    }

    public function setVariantLabel(string $variantLabel): static
    {
        $this->variantLabel = $variantLabel;

        return $this;
    }

    public function getProductPrice(): ?string
    {
        return $this->productPrice;
    }

    public function setProductPrice(string $productPrice): static
    {
        $this->productPrice = $productPrice;

        return $this;
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function setQty(int $qty): static
    {
        $this->qty = $qty;

        return $this;
    }

    public function getVariant(): ?ProductVariant
    {
        return $this->variant;
    }

    public function setVariant(?ProductVariant $variant): static
    {
        $this->variant = $variant;

        return $this;
    }
}
