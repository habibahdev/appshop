<?php

namespace App\Entity;

use App\Enum\StockMovementType;
use App\Repository\StockMovementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ligne d'historique d'une variation de {@see Stock} - entrés, sortie...
 * Constitue le journal d'audit du stock.
 *
 * @package App\Entity
 *
 * @property-read int|null $id
 * @property Stock|null $stock
 * @property StockMovementType|null $type Nature du mouvement.
 * @property int|null $qty Toujours positive; le sens est porté par {@see $type}.
 * @property string|null $reason Motif.
 * @property Purchase|null $purchase Commande à l'origine du mouvement, si applicable.
 * @property-read \DateTimeImmutable|null $createdAt
 */
#[ORM\Entity(repositoryClass: StockMovementRepository::class)]
class StockMovement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stockMovements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stock $stock = null;

    #[ORM\Column(length: 255, enumType: StockMovementType::class)]
    private ?StockMovementType $type = null;

    #[ORM\Column]
    private ?int $qty = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\ManyToOne(inversedBy: 'stockMovements')]
    private ?Purchase $purchase = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getType(): ?StockMovementType
    {
        return $this->type;
    }

    public function setType(StockMovementType $type): static
    {
        $this->type = $type;

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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Quantité signée selon le type de mouvement.
     *
     * @return integer Négatif pour {@see StockMovementType::OUTING}
     * et {@see StockMovementType::SALE}, positif sinon
     */
    public function getSignedQty(): int
    {
        return in_array($this->type, [StockMovementType::OUTING, StockMovementType::SALE], true)
            ? -$this->qty
            : $this->qty;
    }
}
