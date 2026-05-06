<?php

namespace App\Entity;

use App\Repository\CheckInsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CheckInsRepository::class)]
#[Table('check_ins')]
class CheckIns
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'checkIns')]
    private ?Visitors $visitor = null;

    #[ORM\ManyToOne(inversedBy: 'checkIns')]
    #[Groups(groups: ['request'])]
    private ?QRCodes $qr_code = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['request'])]
    private ?\DateTimeInterface $check_in_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['request'])]
    private ?\DateTimeInterface $check_out_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

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

    public function getQrCode(): ?QRCodes
    {
        return $this->qr_code;
    }

    public function setQrCode(?QRCodes $qr_code): static
    {
        $this->qr_code = $qr_code;

        return $this;
    }

    public function getCheckInTime(): ?\DateTimeInterface
    {
        return $this->check_in_time;
    }

    public function setCheckInTime(?\DateTimeInterface $check_in_time): static
    {
        $this->check_in_time = $check_in_time;

        return $this;
    }

    public function getCheckOutTime(): ?\DateTimeInterface
    {
        return $this->check_out_time;
    }

    public function setCheckOutTime(?\DateTimeInterface $check_out_time): static
    {
        $this->check_out_time = $check_out_time;

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
}
