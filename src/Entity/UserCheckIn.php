<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserCheckInRepository;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: UserCheckInRepository::class)]
#[ORM\Table(name: "user_check_ins")]
class UserCheckIn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QRUser::class, inversedBy: 'userCheckIns')]
    private ?QRUser $qr_user = null;

    #[ORM\Column(type: "datetime")]
    private DateTimeInterface $check_in_time;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTimeInterface $check_out_time = null;

    #[ORM\Column(type: "datetime")]
    private DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private DateTimeInterface $updated_at;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $check_log = null;

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getCheckLog(): ?array
    {
        return $this->check_log;
    }

    public function setCheckLog(?array $check_log): self
    {
        $this->check_log = $check_log;
        return $this;
    }
    // public function getUserId(): int
    // {
    //     return $this->userId;
    // }

    // public function setUserId(int $userId): self
    // {
    //     $this->userId = $userId;
    //     return $this;
    // }

    // ✅ Corrected Getter and Setter for qr_user
    public function getQrUser(): ?QRUser
    {
        return $this->qr_user;
    }

    public function setQrUser(?QRUser $qr_user): self
    {
        $this->qr_user = $qr_user;
        return $this;
    }

    public function getCheckInTime(): DateTimeInterface
    {
        return $this->check_in_time;
    }

    public function setCheckInTime(DateTimeInterface $check_in_time): self
    {
        $this->check_in_time = $check_in_time;
        return $this;
    }

    public function getCheckOutTime(): ?DateTimeInterface
    {
        return $this->check_out_time;
    }

    public function setCheckOutTime(?DateTimeInterface $check_out_time): self
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
    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }
}
