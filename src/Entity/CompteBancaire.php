<?php

namespace App\Entity;

use App\Repository\CompteBancaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompteBancaireRepository::class)]
#[ORM\Table(name: 'comptes_bancaires')]
class CompteBancaire
{
    const TYPE_COURANT = 'courant';
    const TYPE_LIVRET_A = 'livret_a';
    const TYPE_PEL = 'pel';

    const STATUS_ACTIF = 'actif';
    const STATUS_BLOQUE = 'bloque';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comptesBancaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 34, unique: true)]
    private ?string $iban = null;

    #[ORM\Column(length: 20)]
    private ?string $type = self::TYPE_COURANT;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $solde = '0.00';

    #[ORM\Column(length: 20)]
    private ?string $statut = self::STATUS_ACTIF;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'compteSource', targetEntity: Transaction::class)]
    private Collection $transactionsEmises;

    #[ORM\OneToMany(mappedBy: 'compteDestinataire', targetEntity: Transaction::class)]
    private Collection $transactionsRecues;

    public function __construct()
    {
        $this->transactionsEmises = new ArrayCollection();
        $this->transactionsRecues = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getIban(): ?string { return $this->iban; }
    public function setIban(string $iban): static { $this->iban = $iban; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_COURANT => 'Compte Courant',
            self::TYPE_LIVRET_A => 'Livret A',
            self::TYPE_PEL => 'PEL',
            default => ucfirst($this->type)
        };
    }

    public function getSolde(): ?string { return $this->solde; }
    public function setSolde(string $solde): static { $this->solde = $solde; return $this; }
    public function getSoldeFloat(): float { return (float) $this->solde; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function isActif(): bool { return $this->statut === self::STATUS_ACTIF; }
    public function isBloque(): bool { return $this->statut === self::STATUS_BLOQUE; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getTransactionsEmises(): Collection { return $this->transactionsEmises; }
    public function getTransactionsRecues(): Collection { return $this->transactionsRecues; }

    public function getAllTransactions(): Collection
    {
        $all = new ArrayCollection();
        foreach ($this->transactionsEmises as $t) { $all->add($t); }
        foreach ($this->transactionsRecues as $t) { $all->add($t); }
        $arr = $all->toArray();
        usort($arr, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        return new ArrayCollection($arr);
    }

    public function getIbanFormatted(): string
    {
        return $this->iban ?? '';
    }
}
