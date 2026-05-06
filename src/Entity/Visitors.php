<?php

namespace App\Entity;

use App\Repository\VisitorsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: VisitorsRepository::class)]
class Visitors
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['visitor', 'request', 'evenements'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'visitors')]
    #[Groups(['visitor'])]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor', 'request', 'evenements'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor', 'request', 'evenements'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor', 'evenements', 'request'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor' , 'request'])]
    private ?string $contact = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor', 'request'])]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor', 'request'])]
    private ?string $organisationName = null;


    #[ORM\Column]
    #[Groups(['visitor'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['visitor'])]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, Requests>
     */
    #[ORM\OneToMany(targetEntity: Requests::class, mappedBy: 'visitor')]
    private Collection $requests;

    /**
     * @var Collection<int, QRCodes>
     */
    #[ORM\OneToMany(targetEntity: QRCodes::class, mappedBy: 'visitor')]
    private Collection $code;

    /**
     * @var Collection<int, CheckIns>
     */
    #[ORM\OneToMany(targetEntity: CheckIns::class, mappedBy: 'visitor')]
    #[Groups(['request'])]
    private Collection $checkIns;

    // #[ORM\Column(length: 255, nullable: true)]
    // private ?string $organisationName = null;

    #[ORM\Column(length: 255)]
    private ?string $idNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $visitor_type = null;

    #[ORM\ManyToOne(inversedBy: 'visitors')]
    #[Groups(['request'])]
    private ?Evenements $evenements = null;

    #[ORM\ManyToOne(inversedBy: 'visitors')]
    private ?Company $company = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor' , 'request'])]
    private ?string $state = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor', 'request'])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor', 'request'])]
    private ?string $zipcode = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor', 'request'])]
    private ?string $country = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor' , 'request'])]
    private ?string $request_date = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor' , 'request'])]
    private ?string $request_time = null;

    #[ORM\Column(length: 255)]
    #[Groups(['visitor' , 'request'])]
    private ?string $request_image = null;

    public function __construct()
    {
        $this->requests = new ArrayCollection();
        $this->code = new ArrayCollection();
        $this->checkIns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestImage(): ?string
    {
        return $this->request_image;
    }

    public function setRequestImage(string $request_image): static
    {
        $this->request_image = $request_image;

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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

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

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipCode(string $zipcode): static
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getRequestDate(): ?string
    {
        return $this->request_date;
    }

    public function setRequestDate(string $request_date): static
    {
        $this->request_date = $request_date;

        return $this;
    }

    public function getRequestTime(): ?string
    {
        return $this->request_time;
    }

    public function setRequestTime(string $request_time): static
    {
        $this->request_time = $request_time;

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

    /**
     * @return Collection<int, Requests>
     */
    public function getRequests(): Collection
    {
        return $this->requests;
    }

    public function addRequest(Requests $request): static
    {
        if (!$this->requests->contains($request)) {
            $this->requests->add($request);
            $request->setVisitor($this);
        }

        return $this;
    }

    public function removeRequest(Requests $request): static
    {
        if ($this->requests->removeElement($request)) {
            // set the owning side to null (unless already changed)
            if ($request->getVisitor() === $this) {
                $request->setVisitor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, QRCodes>
     */
    public function getCode(): Collection
    {
        return $this->code;
    }

    public function addCode(QRCodes $code): static
    {
        if (!$this->code->contains($code)) {
            $this->code->add($code);
            $code->setVisitor($this);
        }

        return $this;
    }

    public function removeCode(QRCodes $code): static
    {
        if ($this->code->removeElement($code)) {
            // set the owning side to null (unless already changed)
            if ($code->getVisitor() === $this) {
                $code->setVisitor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CheckIns>
     */
    public function getCheckIns(): Collection
    {
        return $this->checkIns;
    }

    public function addCheckIn(CheckIns $checkIn): static
    {
        if (!$this->checkIns->contains($checkIn)) {
            $this->checkIns->add($checkIn);
            $checkIn->setVisitor($this);
        }

        return $this;
    }

    public function removeCheckIn(CheckIns $checkIn): static
    {
        if ($this->checkIns->removeElement($checkIn)) {
            // set the owning side to null (unless already changed)
            if ($checkIn->getVisitor() === $this) {
                $checkIn->setVisitor(null);
            }
        }

        return $this;
    }

    public function getOrganisationName(): ?string
    {
        return $this->organisationName;
    }

    public function setOrganisationName(?string $organisationName): static
    {
        $this->organisationName = $organisationName;

        return $this;
    }

    public function getIdNumber(): ?string
    {
        return $this->idNumber;
    }

    public function setIdNumber(string $idNumber): static
    {
        $this->idNumber = $idNumber;

        return $this;
    }

    public function getVisitorType(): ?int
    {
        return $this->visitor_type;
    }

    public function setVisitorType(?int $visitor_type): static
    {
        $this->visitor_type = $visitor_type;

        return $this;
    }

    public function getEvenements(): ?Evenements
    {
        return $this->evenements;
    }

    public function setEvenements(?Evenements $evenements): static
    {
        $this->evenements = $evenements;

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
