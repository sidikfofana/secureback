<?php

namespace App\Entity;

use App\Repository\RequestsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RequestsRepository::class)]
class Requests
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['request'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'requests')]
    #[Groups(['request'])]
    private ?Visitors $visitor = null;

    #[ORM\ManyToOne(inversedBy: 'requests')]
    #[Groups(['request'])]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Groups(['request'])]
    private ?string $host = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['request'])]
    private ?bool $status = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['request'])]
    private ?bool $confirmed = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['request'])]
    private ?\DateTime $request_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['request'])]
    private ?\DateTime $response_date = null;

    #[ORM\Column]
    #[Groups(['request'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['request'])]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVisitor(): ?Visitors
    {
        return $this->visitor;
    }

    public function setVisitor(?Visitors $visitor): static
    {
        $this->visitor = $visitor;

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

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isConfirmed(): ?bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(?bool $confirmed): static
    {
        $this->confirmed = $confirmed;

        return $this;
    }

    public function getRequestDate(): ?\DateTime
    {
        return $this->request_date;
    }

    public function setRequestDate(\DateTime $request_date): static
    {
        $this->request_date = $request_date;

        return $this;
    }

    public function getResponseDate(): ?\DateTime
    {
        return $this->response_date;
    }

    public function setResponseDate(?\DateTime $response_date): static
    {
        $this->response_date = $response_date;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
