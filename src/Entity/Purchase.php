<?php

namespace App\Entity;

use App\Enum\PurchaseStatus;
use App\Repository\PurchaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Commande passée par un {@see User}. Pivot central du tunnel d'achat.
 *
 * Le champ {@see $status} est piloté exclusivement par le composant
 * workflow Symfony (`purchase_status`) - ne jamais appeler {@see setStatus()}
 * directement en dehors de {@see \App\Service\PurchaseService}.
 *
 * @package App\Entity
 *
 * @property-read int|null $id
 * @property string|null $reference Identifiant public unique.
 * @property User|null $user
 * @property PurchaseStatus|null $status
 * @property string|null $total Montant final (decimal).
 * @property string|null $discount Montant de réduction appliqué (decimal).
 * @property Coupon|null $coupon
 * @property string|null $delivery Adresse figée en texte au moment de l'achat (snapshot indépendant de [@see Address])
 * @property string|null $stripe ID du PaymentIntentStripe.
 * @property Collection<int, Detail> $details
 * @property Invoice|null $invoice
 * @property Collection<int, StockMovement> $stockMovements
 * @property-read \DateTimeImmutable|null $createdAt
 */
#[ORM\Entity(repositoryClass: PurchaseRepository::class)]
class Purchase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(inversedBy: 'purchases')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, enumType: PurchaseStatus::class)]
    private ?PurchaseStatus $status = PurchaseStatus::PENDING;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $discount = '0.00';

    #[ORM\ManyToOne(inversedBy: 'purchases')]
    private ?Coupon $coupon = null;

    #[ORM\Column(length: 255)]
    private ?string $delivery = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripe = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Detail>
     */
    #[ORM\OneToMany(targetEntity: Detail::class, mappedBy: 'purchase', cascade: ['persist', 'remove'])]
    private Collection $details;

    #[ORM\OneToOne(mappedBy: 'purchase', cascade: ['persist', 'remove'])]
    private ?Invoice $invoice = null;

    /**
     * @var Collection<int, StockMovement>
     */
    #[ORM\OneToMany(targetEntity: StockMovement::class, mappedBy: 'purchase')]
    private Collection $stockMovements;

    public function __construct()
    {
        $this->details = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->stockMovements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?PurchaseStatus
    {
        return $this->status;
    }

    public function setStatus(PurchaseStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getDiscount(): ?string
    {
        return $this->discount;
    }

    public function setDiscount(string $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): static
    {
        $this->coupon = $coupon;

        return $this;
    }

    public function getDelivery(): ?string
    {
        return $this->delivery;
    }

    public function setDelivery(string $delivery): static
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getStripe(): ?string
    {
        return $this->stripe;
    }

    public function setStripe(?string $stripe): static
    {
        $this->stripe = $stripe;

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
     * @return Collection<int, Detail>
     */
    public function getDetails(): Collection
    {
        return $this->details;
    }

    public function addDetail(Detail $detail): static
    {
        if (!$this->details->contains($detail)) {
            $this->details->add($detail);
            $detail->setPurchase($this);
        }

        return $this;
    }

    public function removeDetail(Detail $detail): static
    {
        if ($this->details->removeElement($detail)) {
            // set the owning side to null (unless already changed)
            if ($detail->getPurchase() === $this) {
                $detail->setPurchase(null);
            }
        }

        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): static
    {
        // set the owning side of the relation if necessary
        if ($invoice->getPurchase() !== $this) {
            $invoice->setPurchase($this);
        }

        $this->invoice = $invoice;

        return $this;
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function getStockMovements(): Collection
    {
        return $this->stockMovements;
    }

    public function addStockMovement(StockMovement $stockMovement): static
    {
        if (!$this->stockMovements->contains($stockMovement)) {
            $this->stockMovements->add($stockMovement);
            $stockMovement->setPurchase($this);
        }

        return $this;
    }

    public function removeStockMovement(StockMovement $stockMovement): static
    {
        if ($this->stockMovements->removeElement($stockMovement)) {
            // set the owning side to null (unless already changed)
            if ($stockMovement->getPurchase() === $this) {
                $stockMovement->setPurchase(null);
            }
        }

        return $this;
    }
}
