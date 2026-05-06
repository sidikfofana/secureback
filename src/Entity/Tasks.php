<?php

namespace App\Entity;

use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use App\Repository\TasksRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TasksRepository::class)]
class Tasks
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tasks', 'comments', 'time_entries'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[Groups(['tasks'])]
    private ?Project $project_id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['tasks', 'time_entries'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['tasks'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[Groups(['users', 'tasks'])]
    private ?User $assigned_to = null;

    #[ORM\Column(enumType: TaskPriority::class)]
    #[Groups(['tasks'])]
    private ?TaskPriority $priority = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['tasks'])]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['tasks'])]
    private ?\DateTimeInterface $due_date = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['tasks'])]
    private ?int $estimated_time = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['tasks'])]
    private ?int $spent_time = null;

    #[ORM\Column(enumType: TaskStatus::class)]
    #[Groups(['tasks'])]
    private ?TaskStatus $status = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'tasks')]
    #[Groups(['tasks', 'projects'])]
    private ?self $parent_task_id = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent_task_id')]
    ##[Groups(['projects', 'tasks'])]
    private Collection $tasks;

    #[ORM\Column]
    #[Groups(['tasks'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    #[Groups(['tasks'])]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, TimeEntries>
     */
    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TimeEntries::class)]
    #[Groups(['tasks'])]
    private Collection $timeEntries;

    /**
     * @var Collection<int, Comments>
     */
    #[ORM\OneToMany(targetEntity: Comments::class, mappedBy: 'task_id')]
    private Collection $comments;

    #[ORM\ManyToOne(inversedBy: 'tasksCreater')]
    #[Groups(['users', 'tasks'])]
    private ?User $created_by = null;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->timeEntries = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): ?Project
    {
        return $this->project_id;
    }

    public function setProjectId(?Project $project_id): static
    {
        $this->project_id = $project_id;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assigned_to;
    }

    public function setAssignedTo(?User $assigned_to): static
    {
        $this->assigned_to = $assigned_to;

        return $this;
    }

    public function getPriority(): ?TaskPriority
    {
        return $this->priority;
    }

    public function setPriority(TaskPriority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(?\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->due_date;
    }

    public function setDueDate(?\DateTimeInterface $due_date): static
    {
        $this->due_date = $due_date;

        return $this;
    }

    public function getEstimatedTime(): ?int
    {
        return $this->estimated_time;
    }

    public function setEstimatedTime(?int $estimated_time): static
    {
        $this->estimated_time = $estimated_time;

        return $this;
    }

    public function getSpentTime(): ?int
    {
        return $this->spent_time;
    }

    public function setSpentTime(?int $spent_time): static
    {
        $this->spent_time = $spent_time;

        return $this;
    }

    public function getStatus(): ?TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getParentTaskId(): ?self
    {
        return $this->parent_task_id;
    }

    public function setParentTaskId(?self $parent_task_id): static
    {
        $this->parent_task_id = $parent_task_id;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(self $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setParentTaskId($this);
        }

        return $this;
    }

    public function removeTask(self $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getParentTaskId() === $this) {
                $task->setParentTaskId(null);
            }
        }

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
            $timeEntry->setTaskId($this);
        }

        return $this;
    }

    public function removeTimeEntry(TimeEntries $timeEntry): static
    {
        if ($this->timeEntries->removeElement($timeEntry)) {
            // set the owning side to null (unless already changed)
            if ($timeEntry->getTaskId() === $this) {
                $timeEntry->setTaskId(null);
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
            $comment->setTaskId($this);
        }

        return $this;
    }

    public function removeComment(Comments $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getTaskId() === $this) {
                $comment->setTaskId(null);
            }
        }

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    public function setCreatedBy(?User $created_by): static
    {
        $this->created_by = $created_by;

        return $this;
    }
}
