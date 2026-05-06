<?php

namespace App\Entity;

use App\Repository\MembersRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MembersRepository::class)]
class Members
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['members'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['members'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(['members'])]
    private ?string $uidn = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['members'])]
    private ?string $dateExp = null;

    #[ORM\Column(length: 255)]
    #[Groups(['members'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['members'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['members'])]
    private ?string $contact = null;

    #[ORM\Column(length: 255)]
    #[Groups(['members'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['members'])]
    private ?string $code = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    ##[Groups(['members', 'company'])]
    #[ORM\JoinColumn(name: "company_id", referencedColumnName: "id", nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['members'])]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(length: 255)]
    #[Groups(['members'])]
    private ?string $user_image = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserImage(): ?string
    {
        return $this->user_image;
    }

    public function setUserImage(string $user_image): static
    {
        $this->user_image = $user_image;

        return $this;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstname;
    }

    public function setFirstName(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastname;
    }

    public function setLastName(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(string $contact): static
    {
        $this->contact = $contact;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDateExp(): ?string
    {
        return $this->dateExp;
    }

    public function setDateExp(string $dateExp): static
    {
        $this->dateExp = $dateExp;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }
}
