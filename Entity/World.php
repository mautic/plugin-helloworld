<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class World
{
    private ?int $id;
    private ?string $world;
    private ?int $isEnabled;

    /**
     * @param ClassMetadata<self> $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder
            ->setTable('hello_world')
            ->setCustomRepositoryClass(WorldRepository::class)
            ->addIndex(['is_enabled'], 'is_enabled');

        $builder->addId();

        $builder
            ->createField('world', Type::STRING)
            ->build();

        $builder
            ->createField('isEnabled', Type::BOOLEAN)
            ->columnName('is_enabled')
            ->build();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getWorld(): string
    {
        return $this->world;
    }

    public function setWorld(string $world): void
    {
        $this->world = $world;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->isEnabled;
    }

    public function getIsEnabled(): int
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(int $isEnabled): void
    {
        $this->isEnabled = $isEnabled;
    }
}
