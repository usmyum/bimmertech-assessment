<?php

namespace App\Entity;

use App\Repository\SoftwareVersionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoftwareVersionRepository::class)]
#[ORM\Table(name: 'software_version')]
class SoftwareVersion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 100)]
    private string $systemVersion = '';

    /**
     * Version string WITHOUT leading "v" — this is what customers type in.
     */
    #[ORM\Column(length: 100)]
    private string $systemVersionAlt = '';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $st = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $gd = null;

    #[ORM\Column]
    private bool $latest = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSystemVersion(): string
    {
        return $this->systemVersion;
    }

    public function setSystemVersion(string $v): static
    {
        $this->systemVersion = $v;
        return $this;
    }

    public function getSystemVersionAlt(): string
    {
        return $this->systemVersionAlt;
    }

    public function setSystemVersionAlt(string $v): static
    {
        $this->systemVersionAlt = $v;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;
        return $this;
    }

    public function getSt(): ?string
    {
        return $this->st;
    }

    public function setSt(?string $st): static
    {
        $this->st = $st;
        return $this;
    }

    public function getGd(): ?string
    {
        return $this->gd;
    }

    public function setGd(?string $gd): static
    {
        $this->gd = $gd;
        return $this;
    }

    public function isLatest(): bool
    {
        return $this->latest;
    }

    public function setLatest(bool $latest): static
    {
        $this->latest = $latest;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name . ' — ' . $this->systemVersion;
    }
}
