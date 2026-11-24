<?php

namespace App\Repository;

use App\Entity\Categoria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Categoria>
 */
class CategoriaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categoria::class);
    }

    /**
     * Lista do admin com a quantidade de eventos de cada categoria.
     *
     * @return list<array{categoria: Categoria, total: int}>
     */
    public function listarComTotais(): array
    {
        $linhas = $this->createQueryBuilder('c')
            ->select('c AS categoria, COUNT(e.id) AS total')
            ->leftJoin('c.eventos', 'e')
            ->groupBy('c.id')
            ->orderBy('c.ativa', 'DESC')
            ->addOrderBy('c.nome', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn (array $l) => ['categoria' => $l['categoria'], 'total' => (int) $l['total']], $linhas);
    }
}
