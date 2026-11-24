<?php

namespace App\Entity;

use App\Repository\CategoriaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoriaRepository::class)]
#[UniqueEntity(fields: ['nome'], message: 'Já existe uma categoria com este nome.')]
class Categoria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80, unique: true)]
    #[Assert\NotBlank(message: 'Informe o nome.')]
    #[Assert\Length(max: 80)]
    private ?string $nome = null;

    /** Cor em hexadecimal (#rrggbb), usada nas etiquetas do site e do admin. */
    #[ORM\Column(length: 7)]
    #[Assert\NotBlank(message: 'Escolha uma cor.')]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG, message: 'Use uma cor no formato #rrggbb.')]
    private ?string $cor = '#6366f1';

    /** Categoria inativa: some do formulário de eventos e seus eventos saem do site. */
    #[ORM\Column]
    private bool $ativa = true;

    /** @var Collection<int, Evento> */
    #[ORM\OneToMany(targetEntity: Evento::class, mappedBy: 'categoria')]
    private Collection $eventos;

    public function __construct()
    {
        $this->eventos = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->nome;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): ?string
    {
        return $this->nome;
    }

    public function setNome(?string $nome): static
    {
        $this->nome = $nome;

        return $this;
    }

    public function getCor(): ?string
    {
        return $this->cor;
    }

    public function setCor(?string $cor): static
    {
        $this->cor = $cor ? strtolower($cor) : null;

        return $this;
    }

    public function isAtiva(): bool
    {
        return $this->ativa;
    }

    public function setAtiva(bool $ativa): static
    {
        $this->ativa = $ativa;

        return $this;
    }

    /** @return Collection<int, Evento> */
    public function getEventos(): Collection
    {
        return $this->eventos;
    }
}
