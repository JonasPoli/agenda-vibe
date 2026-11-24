<?php

namespace App\Repository;

use App\Entity\Evento;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evento>
 */
class EventoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evento::class);
    }

    /**
     * Eventos visíveis no site: evento ativo e categoria ativa.
     */
    private function visiveisNoSite(): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->addSelect('c')
            ->innerJoin('e.categoria', 'c')
            ->andWhere('e.ativo = true')
            ->andWhere('c.ativa = true');
    }

    /**
     * Eventos visíveis que ainda não terminaram, do mais próximo ao mais distante.
     *
     * @return Evento[]
     */
    public function proximosVisiveis(?int $limite = null): array
    {
        return $this->visiveisNoSite()
            ->andWhere('COALESCE(e.fim, e.inicio) >= :agora')
            ->setParameter('agora', new \DateTimeImmutable())
            ->orderBy('e.inicio', 'ASC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    /**
     * Destaques que aparecem na home: visíveis, ainda não terminados, do mais próximo ao mais distante.
     *
     * @return Evento[]
     */
    public function destaquesVisiveis(): array
    {
        return $this->visiveisNoSite()
            ->andWhere('e.destaque = true')
            ->andWhere('COALESCE(e.fim, e.inicio) >= :agora')
            ->setParameter('agora', new \DateTimeImmutable())
            ->orderBy('e.inicio', 'ASC')
            ->setMaxResults(Evento::MAX_DESTAQUES)
            ->getQuery()
            ->getResult();
    }

    /**
     * Eventos marcados como destaque que ainda não terminaram (ocupam vaga no limite),
     * estejam ou não ativos.
     *
     * @return Evento[]
     */
    public function destaquesVigentes(?int $excetoId = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.destaque = true')
            ->andWhere('COALESCE(e.fim, e.inicio) >= :agora')
            ->setParameter('agora', new \DateTimeImmutable())
            ->orderBy('e.inicio', 'ASC');

        if ($excetoId) {
            $qb->andWhere('e.id <> :id')->setParameter('id', $excetoId);
        }

        return $qb->getQuery()->getResult();
    }

    public function visivelPorSlug(string $slug): ?Evento
    {
        return $this->visiveisNoSite()
            ->andWhere('e.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Lista do admin, com busca opcional por título ou local e filtros.
     *
     * @param 'ativos'|'inativos'|'destaques'|null $situacao
     *
     * @return Evento[]
     */
    public function buscarAdmin(?string $termo = null, ?int $categoriaId = null, ?string $situacao = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->addSelect('c')
            ->innerJoin('e.categoria', 'c')
            ->orderBy('e.inicio', 'DESC');

        if ($termo) {
            $qb->andWhere('e.titulo LIKE :t OR e.local LIKE :t')
                ->setParameter('t', '%'.$termo.'%');
        }

        if ($categoriaId) {
            $qb->andWhere('c.id = :cat')->setParameter('cat', $categoriaId);
        }

        if ('ativos' === $situacao) {
            $qb->andWhere('e.ativo = true');
        } elseif ('inativos' === $situacao) {
            $qb->andWhere('e.ativo = false');
        } elseif ('destaques' === $situacao) {
            $qb->andWhere('e.destaque = true');
        }

        return $qb->getQuery()->getResult();
    }

    public function contarProximos(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('COALESCE(e.fim, e.inicio) >= :agora')
            ->setParameter('agora', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function contarInativos(): int
    {
        return $this->count(['ativo' => false]);
    }
}
