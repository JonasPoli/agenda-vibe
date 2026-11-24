<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Já existe um usuário com este e-mail.')]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Informe o e-mail.')]
    #[Assert\Email(message: 'E-mail inválido.')]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Informe o nome.')]
    #[Assert\Length(max: 120)]
    private ?string $nome = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    /** Hash da senha (nunca a senha em texto). */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $criadoEm = null;

    #[ORM\PrePersist]
    public function aoCriar(): void
    {
        $this->criadoEm = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email ? mb_strtolower(trim($email)) : null;

        return $this;
    }

    public function getNome(): ?string
    {
        return $this->nome;
    }

    public function setNome(?string $nome): static
    {
        $this->nome = $nome ? trim($nome) : null;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Todo usuário tem ROLE_USER; o painel exige ROLE_ADMIN.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function isAdmin(): bool
    {
        return \in_array(self::ROLE_ADMIN, $this->getRoles(), true);
    }

    public function tornarAdmin(): static
    {
        return $this->setRoles([...$this->roles, self::ROLE_ADMIN]);
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

    public function getCriadoEm(): ?\DateTimeImmutable
    {
        return $this->criadoEm;
    }

    /** Não guarda a senha em texto na sessão (o hash é suficiente). */
    public function __serialize(): array
    {
        $dados = (array) $this;
        $dados["\0".self::class."\0password"] = hash('crc32c', (string) $this->password);

        return $dados;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
