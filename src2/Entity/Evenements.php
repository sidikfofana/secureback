<?php

namespace App\Entity;

use App\Repository\EvenementsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\SluggerInterface;

#[ORM\Entity(repositoryClass: EvenementsRepository::class)]
#[UniqueEntity(fields: ['name', 'departement', 'date_event'], message: 'There is already an event with this name, department, and creation date.')]
class Evenements
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['evenements', 'request'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements'])]
    private ?string $location = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['evenements'])]
    private ?\DateTimeInterface $date_event = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['evenements'])]
    private ?\DateTimeInterface $time_event = null;

    #[ORM\ManyToOne(inversedBy: 'evenements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['evenements'])]
    private ?Departements $departement = null;

    #[ORM\Column]
    #[Groups(['evenements'])]

    private ?bool $status = null;

    #[ORM\Column]
    #[Groups(['departements', 'evenements'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\ManyToOne(inversedBy: 'evenements')]
    #[Groups(['evenements'])]
    private ?Company $company = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['evenements'])]
    private ?string $slug = null;

    /**
     * @var Collection<int, Visitors>
     */
    #[ORM\OneToMany(targetEntity: Visitors::class, mappedBy: 'evenements')]
    #[Groups(['evenements'])]

    private Collection $visitors;

    #[ORM\Column(length: 255)]
    private ?string $addressName = null;

    public function __construct()
    {
        $this->visitors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function generateSlug(SluggerInterface $slugger): self
    {
        $this->slug = $slugger->slug(strtolower($this->name))->toString();
        return $this;
    }


    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getDateEvent(): ?\DateTimeInterface
    {
        return $this->date_event;
    }

    public function setDateEvent(\DateTimeInterface $date_event): static
    {
        $this->date_event = $date_event;

        return $this;
    }

    public function getTimeEvent(): ?\DateTimeInterface
    {
        return $this->time_event;
    }

    public function setTimeEvent(\DateTimeInterface $time_event): static
    {
        $this->time_event = $time_event;

        return $this;
    }

    public function getDepartement(): ?Departements
    {
        return $this->departement;
    }

    public function setDepartement(?Departements $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

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

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, Visitors>
     */
    public function getVisitors(): Collection
    {
        return $this->visitors;
    }

    public function addVisitor(Visitors $visitor): static
    {
        if (!$this->visitors->contains($visitor)) {
            $this->visitors->add($visitor);
            $visitor->setEvenements($this);
        }

        return $this;
    }

    public function removeVisitor(Visitors $visitor): static
    {
        if ($this->visitors->removeElement($visitor)) {
            // set the owning side to null (unless already changed)
            if ($visitor->getEvenements() === $this) {
                $visitor->setEvenements(null);
            }
        }

        return $this;
    }

    public function getAddressName(): ?string
    {
        return $this->addressName;
    }

    public function setAddressName(string $addressName): static
    {
        $this->addressName = $addressName;

        return $this;
    }
}
