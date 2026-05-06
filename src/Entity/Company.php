<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Members;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['company', 'qruser', 'members', 'projects', 'users'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['evenements', 'company', 'projects', 'users'])]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Members::class, cascade: ['persist', 'remove'])]
    #[Groups(['company'])]
    private Collection $members;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['evenements',"company"])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $id_number = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $phone_number = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $company_field = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $country = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $zipcode = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $number_of_employee = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $point_contact = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $state = null;
    
    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $title = null;

    /**
     * @var Collection<int, Evenements>
     */
    #[ORM\OneToMany(targetEntity: Evenements::class, mappedBy: 'company')]
    private Collection $evenements;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenements','company'])]
    private ?string $logo = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'company')]
    private Collection $users;

    /**
     * @var Collection<int, Visitors>
     */
    #[ORM\OneToMany(targetEntity: Visitors::class, mappedBy: 'company')]
    private Collection $visitors;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'company')]
    private Collection $projects;

    /**
     * @var Collection<int, Documents>
     */
    #[ORM\OneToMany(targetEntity: Documents::class, mappedBy: 'company')]
    private Collection $documents;

    public function __construct()
    {
        $this->evenements = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->visitors = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    #[Groups(['company'])]
    public function getMembersCount(): int
    {
        return $this->getMembers()->count();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getIdNumber(): ?string
    {
        return $this->id_number;
    }

    public function setIdNumber(?string $id_number): static
    {
        $this->id_number = $id_number;

        return $this;
    }

    public function getPointContact(): ?string
    {
        return $this->point_contact;
    }

    public function setPointContact(?string $point_contact): static
    {
        $this->point_contact = $point_contact;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getCompanyField(): ?string
    {
        return $this->company_field;
    }

    public function setCompanyField(?string $company_field): static
    {
        $this->company_field = $company_field;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): static
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getNumberOfEmployee(): ?string
    {
        return $this->number_of_employee;
    }

    public function setNumberOfEmployee(?string $number_of_employee): static
    {
        $this->number_of_employee = $number_of_employee;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, Evenements>
     */
    public function getEvenements(): Collection
    {
        return $this->evenements;
    }

    public function addEvenement(Evenements $evenement): static
    {
        if (!$this->evenements->contains($evenement)) {
            $this->evenements->add($evenement);
            $evenement->setCompany($this);
        }

        return $this;
    }

    public function removeEvenement(Evenements $evenement): static
    {
        if ($this->evenements->removeElement($evenement)) {
            // set the owning side to null (unless already changed)
            if ($evenement->getCompany() === $this) {
                $evenement->setCompany(null);
            }
        }

        return $this;
    }

    public function generateSlug(SluggerInterface $slugger): self
    {
        $this->slug = $slugger->slug(strtolower($this->name))->toString();
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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setCompany($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getCompany() === $this) {
                $user->setCompany(null);
            }
        }

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
            $visitor->setCompany($this);
        }

        return $this;
    }

    public function removeVisitor(Visitors $visitor): static
    {
        if ($this->visitors->removeElement($visitor)) {
            // set the owning side to null (unless already changed)
            if ($visitor->getCompany() === $this) {
                $visitor->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Members>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(Members $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members[] = $member;
            $member->setCompany($this);
        }

        return $this;
    }

    public function removeMember(Members $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getCompany() === $this) {
                $member->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setCompany($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getCompany() === $this) {
                $project->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Documents>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Documents $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setCompany($this);
        }

        return $this;
    }

    public function removeDocument(Documents $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getCompany() === $this) {
                $document->setCompany(null);
            }
        }

        return $this;
    }
}