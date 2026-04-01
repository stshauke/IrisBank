<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
class Transaction
{
    const TYPE_DEPOT = 'depot';
    const TYPE_RETRAIT = 'retrait';
    const TYPE_VIREMENT_EMIS = 'virement_emis';
    const TYPE_VIREMENT_RECU = 'virement_recu';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactionsEmises')]
    #[ORM\JoinColumn(nullable: true)]
    private ?CompteBancaire $compteSource = null;

    #[ORM\ManyToOne(inversedBy: 'transactionsRecues')]
    #[ORM\JoinColumn(nullable: true)]
    private ?CompteBancaire $compteDestinataire = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CarteVirtuelle $carteVirtuelle = null;

    #[ORM\Column(length: 30)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCompteSource(): ?CompteBancaire { return $this->compteSource; }
    public function setCompteSource(?CompteBancaire $compteSource): static { $this->compteSource = $compteSource; return $this; }

    public function getCompteDestinataire(): ?CompteBancaire { return $this->compteDestinataire; }
    public function setCompteDestinataire(?CompteBancaire $compteDestinataire): static { $this->compteDestinataire = $compteDestinataire; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_DEPOT => 'Dépôt',
            self::TYPE_RETRAIT => 'Retrait',
            self::TYPE_VIREMENT_EMIS => 'Virement émis',
            self::TYPE_VIREMENT_RECU => 'Virement reçu',
            default => ucfirst($this->type)
        };
    }

    public function isCredit(): bool
    {
        return in_array($this->type, [self::TYPE_DEPOT, self::TYPE_VIREMENT_RECU]);
    }

    public function getMontant(): ?string { return $this->montant; }
    public function setMontant(string $montant): static { $this->montant = $montant; return $this; }
    public function getMontantFloat(): float { return (float) $this->montant; }

    public function getLibelle(): ?string { return $this->libelle; }
    public function setLibelle(?string $libelle): static { $this->libelle = $libelle; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getCarteVirtuelle(): ?CarteVirtuelle { return $this->carteVirtuelle; }
    public function setCarteVirtuelle(?CarteVirtuelle $carte): static { $this->carteVirtuelle = $carte; return $this; }
}
