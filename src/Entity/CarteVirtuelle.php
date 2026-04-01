<?php

namespace App\Entity;

use App\Repository\CarteVirtuelleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarteVirtuelleRepository::class)]
#[ORM\Table(name: 'cartes_virtuelles')]
class CarteVirtuelle
{
    const TYPE_VISA       = 'visa';
    const TYPE_MASTERCARD = 'mastercard';

    const STATUT_ACTIVE   = 'active';
    const STATUT_RESILIEE = 'resiliee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cartesVirtuelles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CompteBancaire $compte = null;

    #[ORM\Column(length: 16)]
    private string $type = self::TYPE_VISA;

    #[ORM\Column(length: 19)]
    private string $numero = '';

    #[ORM\Column(length: 3)]
    private string $cvv = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateExpiration;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_ACTIVE;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'carteVirtuelle', targetEntity: Transaction::class)]
    private Collection $transactions;

    public function __construct()
    {
        $this->createdAt      = new \DateTimeImmutable();
        $this->dateExpiration = new \DateTimeImmutable('+3 years');
        $this->transactions   = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCompte(): ?CompteBancaire { return $this->compte; }
    public function setCompte(?CompteBancaire $compte): static { $this->compte = $compte; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getNumero(): string { return $this->numero; }
    public function setNumero(string $numero): static { $this->numero = $numero; return $this; }

    public function getNumeroMasque(): string
    {
        return '**** **** **** ' . substr(str_replace(' ', '', $this->numero), -4);
    }

    public function getCvv(): string { return $this->cvv; }
    public function setCvv(string $cvv): static { $this->cvv = $cvv; return $this; }

    public function getDateExpiration(): \DateTimeImmutable { return $this->dateExpiration; }
    public function setDateExpiration(\DateTimeImmutable $date): static { $this->dateExpiration = $date; return $this; }

    public function getDateExpirationFormatee(): string
    {
        return $this->dateExpiration->format('m/y');
    }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function isActive(): bool { return $this->statut === self::STATUT_ACTIVE; }
    public function isResiliee(): bool { return $this->statut === self::STATUT_RESILIEE; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getTransactions(): Collection { return $this->transactions; }
}
