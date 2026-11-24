<?php

namespace App\Entity;

use App\Repository\EventoRepository;
use App\Validator\LimiteDestaques;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventoRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Já existe um evento com este endereço (slug).')]
#[LimiteDestaques]
class Evento
{
    /** Quantos eventos (que ainda não terminaram) podem estar em destaque ao mesmo tempo. */
    public const MAX_DESTAQUES = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank(message: 'Informe o título.')]
    #[Assert\Length(max: 160)]
    private ?string $titulo = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $resumo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descricao = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank(message: 'Informe o local.')]
    #[Assert\Length(max: 160)]
    private ?string $local = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Informe a data e a hora de início.')]
    private ?\DateTimeImmutable $inicio = null;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'inicio', message: 'O término deve ser depois do início.')]
    private ?\DateTimeImmutable $fim = null;

    #[ORM\ManyToOne(inversedBy: 'eventos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'Escolha a categoria.')]
    private ?Categoria $categoria = null;

    /** Evento inativo não aparece no site (funciona como rascunho). */
    #[ORM\Column]
    private bool $ativo = false;

    /** Aparece no bloco de destaques do topo da home. */
    #[ORM\Column(options: ['default' => false])]
    private bool $destaque = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $criadoEm = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $atualizadoEm = null;

    #[ORM\PrePersist]
    public function aoCriar(): void
    {
        $this->criadoEm = new \DateTimeImmutable();
        $this->atualizadoEm = $this->criadoEm;
    }

    #[ORM\PreUpdate]
    public function aoAtualizar(): void
    {
        $this->atualizadoEm = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(?string $titulo): static
    {
        $this->titulo = $titulo;

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

    public function getResumo(): ?string
    {
        return $this->resumo;
    }

    public function setResumo(?string $resumo): static
    {
        $this->resumo = $resumo;

        return $this;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): static
    {
        $this->descricao = $descricao;

        return $this;
    }

    public function getLocal(): ?string
    {
        return $this->local;
    }

    public function setLocal(?string $local): static
    {
        $this->local = $local;

        return $this;
    }

    public function getInicio(): ?\DateTimeImmutable
    {
        return $this->inicio;
    }

    public function setInicio(?\DateTimeImmutable $inicio): static
    {
        $this->inicio = $inicio;

        return $this;
    }

    public function getFim(): ?\DateTimeImmutable
    {
        return $this->fim;
    }

    public function setFim(?\DateTimeImmutable $fim): static
    {
        $this->fim = $fim;

        return $this;
    }

    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(?Categoria $categoria): static
    {
        $this->categoria = $categoria;

        return $this;
    }

    public function isAtivo(): bool
    {
        return $this->ativo;
    }

    public function setAtivo(bool $ativo): static
    {
        $this->ativo = $ativo;

        return $this;
    }

    public function isDestaque(): bool
    {
        return $this->destaque;
    }

    public function setDestaque(bool $destaque): static
    {
        $this->destaque = $destaque;

        return $this;
    }

    /** Aparece no site: evento ativo numa categoria ativa. */
    public function isVisivelNoSite(): bool
    {
        return $this->ativo && $this->categoria?->isAtiva();
    }

    public function getCriadoEm(): ?\DateTimeImmutable
    {
        return $this->criadoEm;
    }

    public function getAtualizadoEm(): ?\DateTimeImmutable
    {
        return $this->atualizadoEm;
    }

    public function jaPassou(): bool
    {
        return ($this->fim ?? $this->inicio) < new \DateTimeImmutable();
    }
}
