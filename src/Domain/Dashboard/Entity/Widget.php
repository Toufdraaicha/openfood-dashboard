<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'widget')]
class Widget
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Dashboard::class, inversedBy: 'widgets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Dashboard $dashboard;

    #[ORM\Column(type: 'string', enumType: WidgetType::class, length: 50)]
    private WidgetType $type;

    #[ORM\Column(type: 'integer')]
    private int $position;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'json')]
    private array $config = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Dashboard $dashboard, WidgetType $type, int $position, ?string $title = null, array $config = [])
    {
        $this->dashboard = $dashboard;
        $this->type      = $type;
        $this->position  = $position;
        $this->title     = $title ?? $type->defaultTitle();
        $this->config    = empty($config) ? $type->defaultConfig() : $config;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function updateConfig(array $config): void   { $this->config = $config; }
    public function updatePosition(int $position): void { $this->position = $position; }
    public function rename(string $title): void         { $this->title = $title; }

    public function getId(): string                    { return $this->id; }
    public function getDashboard(): Dashboard          { return $this->dashboard; }
    public function getType(): WidgetType              { return $this->type; }
    public function getPosition(): int                 { return $this->position; }
    public function getTitle(): string                 { return $this->title; }
    public function getConfig(): array                 { return $this->config; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
