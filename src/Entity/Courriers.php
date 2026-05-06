<?php

namespace App\Entity;

use App\Repository\CourriersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourriersRepository::class)]
class Courriers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $objet = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_destinateur = null;

    #[ORM\Column(length: 255)]
    private ?string $prenoms_destinateur = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_destinateur = null;

    #[ORM\Column(length: 255)]
    private ?string $email_destinateur = null;

    #[ORM\Column(length: 255)]
    private ?string $contact_destinateur = null;

    #[ORM\Column(length: 255)]
    private ?string $lieu = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signature = null;

    #[ORM\Column]
    private ?int $civilite = null;

    #[ORM\Column(length: 255)]
    private ?string $destinataire = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'courriers')]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Company $company = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'courriers')]
    #[ORM\JoinColumn(nullable: true)] // ou true selon ton besoin
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'validated_by_id', referencedColumnName: 'id', nullable: true)]
    private ?User $validated_by = null;

    #[ORM\Column(length: 255)]
    private ?string $uidn = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): static
    {
        $this->objet = $objet;

        return $this;
    }

    public function getNomDestinateur(): ?string
    {
        return $this->nom_destinateur;
    }

    public function setNomDestinateur(string $nom_destinateur): static
    {
        $this->nom_destinateur = $nom_destinateur;

        return $this;
    }

    public function getPrenomsDestinateur(): ?string
    {
        return $this->prenoms_destinateur;
    }

    public function setPrenomsDestinateur(string $prenoms_destinateur): static
    {
        $this->prenoms_destinateur = $prenoms_destinateur;

        return $this;
    }

    public function getAdresseDestinateur(): ?string
    {
        return $this->adresse_destinateur;
    }

    public function setAdresseDestinateur(string $adresse_destinateur): static
    {
        $this->adresse_destinateur = $adresse_destinateur;

        return $this;
    }

    public function getEmailDestinateur(): ?string
    {
        return $this->email_destinateur;
    }

    public function setEmailDestinateur(string $email_destinateur): static
    {
        $this->email_destinateur = $email_destinateur;

        return $this;
    }

    public function getContactDestinateur(): ?string
    {
        return $this->contact_destinateur;
    }

    public function setContactDestinateur(string $contact_destinateur): static
    {
        $this->contact_destinateur = $contact_destinateur;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function getCivilite(): ?int
    {
        return $this->civilite;
    }

    public function setCivilite(int $civilite): static
    {
        $this->civilite = $civilite;
        return $this;
    }

    public function getDestinataire(): ?string
    {
        return $this->destinataire;
    }

    public function setDestinataire(string $destinataire): static
    {
        $this->destinataire = $destinataire;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validated_by;
    }

    public function setValidatedBy(?User $validated_by): static
    {
        $this->validated_by = $validated_by;

        return $this;
    }

    public function getUidn(): ?string
    {
        return $this->uidn;
    }

    public function setUidn(string $uidn): static
    {
        $this->uidn = $uidn;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    #[ORM\OneToMany(mappedBy: 'courrier', targetEntity: CommentCourriers::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

}