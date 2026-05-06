<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['users', 'visitor', 'request', 'projects', 'tasks', 'comments', 'time_entries', 'comments'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['users', 'visitor', 'request', 'projects', 'tasks', 'comments', 'time_entries'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['users', 'visitor', 'request', 'projects', 'tasks', 'comments', 'time_entries'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['users'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::ARRAY)]
    #[Groups(['users'])]
    private array $role = [];

    #[ORM\Column(length: 255)]
    #[Groups(['users'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['users'])]
    private ?\DateTimeInterface $create_at = null;

    #[ORM\Column]
    #[Groups(['users'])]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\ManyToOne(targetEntity: Departements::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['users'])]
    private ?Departements $department = null;

    /**
     * @var Collection<int, Visitors>
     */
    #[ORM\OneToMany(targetEntity: Visitors::class, mappedBy: 'user')]
    ##[Groups(['users'])]
    private Collection $visitors;

    /**
     * @var Collection<int, Requests>
     */
    #[ORM\OneToMany(targetEntity: Requests::class, mappedBy: 'user')]
    ##[Groups(['users'])]
    private Collection $requests;

    #[ORM\Column]
    #[Groups(['users'])]
    private ?bool $status = null;

    #[ORM\Column(length: 255)]
    private ?string $contact = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(['users'])]
    private ?Company $company = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'created_by')]
    private Collection $user_id;

    /**
     * @var Collection<int, Tasks>
     */
    #[ORM\OneToMany(targetEntity: Tasks::class, mappedBy: 'assigned_to')]
    private Collection $tasks;

    /**
     * @var Collection<int, TimeEntries>
     */
    #[ORM\OneToMany(targetEntity: TimeEntries::class, mappedBy: 'user_id')]
    private Collection $timeEntries;

    /**
     * @var Collection<int, Comments>
     */
    #[ORM\OneToMany(targetEntity: Comments::class, mappedBy: 'user_id')]
    private Collection $comments;

    /**
     * @var Collection<int, Tasks>
     */
    #[ORM\OneToMany(targetEntity: Tasks::class, mappedBy: 'created_by')]
    private Collection $tasksCreater;

    /**
     * @var Collection<int, Documents>
     */
    #[ORM\OneToMany(targetEntity: Documents::class, mappedBy: 'user_id')]
    private Collection $documents;

    /**
     * @var Collection<int, Documents>
     */
    #[ORM\OneToMany(targetEntity: Documents::class, mappedBy: 'validated_by')]
    private Collection $validated_user;

    /**
     * @var Collection<int, Documents>
     */
    #[ORM\OneToMany(targetEntity: Documents::class, mappedBy: 'approved_by')]
    private Collection $approved_user;

    public function __construct()
    {
        $this->visitors = new ArrayCollection();
        $this->requests = new ArrayCollection();
        $this->user_id = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->timeEntries = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->tasksCreater = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->validated_user = new ArrayCollection();
        $this->approved_user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getRole(): array
    {
        return $this->role;
        $role[] = 'ROLE_USER';
        return array_unique($role);
    }

    public function setRole(array $role): static
    {
        $this->role = $role;
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

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->create_at;
    }

    public function setCreateAt(\DateTimeInterface $create_at): static
    {
        $this->create_at = $create_at;
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

    public function getDepartment(): ?Departements
    {
        return $this->department;
    }

    public function setDepartment(?Departements $department): static
    {
        $this->department = $department;
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
            $visitor->setUser($this);
        }

        return $this;
    }

    public function removeVisitor(Visitors $visitor): static
    {
        if ($this->visitors->removeElement($visitor)) {
            // set the owning side to null (unless already changed)
            if ($visitor->getUser() === $this) {
                $visitor->setUser(null);
            }
        }

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
            $request->setUser($this);
        }

        return $this;
    }

    public function removeRequest(Requests $request): static
    {
        if ($this->requests->removeElement($request)) {
            // set the owning side to null (unless already changed)
            if ($request->getUser() === $this) {
                $request->setUser(null);
            }
        }

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

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getRoles(): array
    {
        // garantie qu'il y ait au moins un rôle "ROLE_USER"
        $roles = $this->role;
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
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

    /**
     * @return Collection<int, Project>
     */
    public function getUserId(): Collection
    {
        return $this->user_id;
    }

    public function addUserId(Project $userId): static
    {
        if (!$this->user_id->contains($userId)) {
            $this->user_id->add($userId);
            $userId->setCreatedBy($this);
        }

        return $this;
    }

    public function removeUserId(Project $userId): static
    {
        if ($this->user_id->removeElement($userId)) {
            // set the owning side to null (unless already changed)
            if ($userId->getCreatedBy() === $this) {
                $userId->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tasks>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Tasks $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setAssignedTo($this);
        }

        return $this;
    }

    public function removeTask(Tasks $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getAssignedTo() === $this) {
                $task->setAssignedTo(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TimeEntries>
     */
    public function getTimeEntries(): Collection
    {
        return $this->timeEntries;
    }

    public function addTimeEntry(TimeEntries $timeEntry): static
    {
        if (!$this->timeEntries->contains($timeEntry)) {
            $this->timeEntries->add($timeEntry);
            $timeEntry->setUserId($this);
        }

        return $this;
    }

    public function removeTimeEntry(TimeEntries $timeEntry): static
    {
        if ($this->timeEntries->removeElement($timeEntry)) {
            // set the owning side to null (unless already changed)
            if ($timeEntry->getUserId() === $this) {
                $timeEntry->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comments>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comments $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setUserId($this);
        }

        return $this;
    }

    public function removeComment(Comments $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getUserId() === $this) {
                $comment->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tasks>
     */
    public function getTasksCreater(): Collection
    {
        return $this->tasksCreater;
    }

    public function addTasksCreater(Tasks $tasksCreater): static
    {
        if (!$this->tasksCreater->contains($tasksCreater)) {
            $this->tasksCreater->add($tasksCreater);
            $tasksCreater->setCreatedBy($this);
        }

        return $this;
    }

    public function removeTasksCreater(Tasks $tasksCreater): static
    {
        if ($this->tasksCreater->removeElement($tasksCreater)) {
            // set the owning side to null (unless already changed)
            if ($tasksCreater->getCreatedBy() === $this) {
                $tasksCreater->setCreatedBy(null);
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
            $document->setUserId($this);
        }

        return $this;
    }

    public function removeDocument(Documents $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getUserId() === $this) {
                $document->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Documents>
     */
    public function getValidatedUser(): Collection
    {
        return $this->validated_user;
    }

    public function addValidatedUser(Documents $validatedUser): static
    {
        if (!$this->validated_user->contains($validatedUser)) {
            $this->validated_user->add($validatedUser);
            $validatedUser->setValidatedBy($this);
        }

        return $this;
    }

    public function removeValidatedUser(Documents $validatedUser): static
    {
        if ($this->validated_user->removeElement($validatedUser)) {
            // set the owning side to null (unless already changed)
            if ($validatedUser->getValidatedBy() === $this) {
                $validatedUser->setValidatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Documents>
     */
    public function getApprovedUser(): Collection
    {
        return $this->approved_user;
    }

    public function addApprovedUser(Documents $approvedUser): static
    {
        if (!$this->approved_user->contains($approvedUser)) {
            $this->approved_user->add($approvedUser);
            $approvedUser->setApprovedBy($this);
        }

        return $this;
    }

    public function removeApprovedUser(Documents $approvedUser): static
    {
        if ($this->approved_user->removeElement($approvedUser)) {
            // set the owning side to null (unless already changed)
            if ($approvedUser->getApprovedBy() === $this) {
                $approvedUser->setApprovedBy(null);
            }
        }

        return $this;
    }
}
