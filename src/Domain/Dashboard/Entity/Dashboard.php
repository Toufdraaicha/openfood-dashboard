<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Entity;

use App\Domain\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'dashboard')]
#[ORM\HasLifecycleCallbacks]
class Dashboard
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'dashboard')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(
        mappedBy: 'dashboard',
        targetEntity: Widget::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $widgets;

    public function __construct(User $user, string $name = 'Mon Dashboard')
    {
        $this->user      = $user;
        $this->name      = $name;
        $this->updatedAt = new \DateTimeImmutable();
        $this->widgets   = new ArrayCollection();
    }

    public function addWidget(WidgetType $type, int $position, ?string $title = null, array $config = []): Widget
    {
        $widget = new Widget($this, $type, $position, $title, $config);
        $this->widgets->add($widget);
        $this->touch();
        return $widget;
    }

    public function removeWidget(Widget $widget): void
    {
        $this->widgets->removeElement($widget);
        $this->touch();
    }

    public function reorderWidgets(array $order): void
    {
        foreach ($this->widgets as $widget) {
            if (isset($order[$widget->getId()])) {
                $widget->updatePosition($order[$widget->getId()]);
            }
        }
        $this->touch();
    }

    public function rename(string $name): void { $this->name = $name; $this->touch(); }

    private function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onUpdate(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): string                    { return $this->id; }
    public function getUser(): User                    { return $this->user; }
    public function getName(): string                  { return $this->name; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getWidgets(): Collection           { return $this->widgets; }
    public function getWidgetCount(): int              { return $this->widgets->count(); }
}
