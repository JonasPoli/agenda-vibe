<?php

namespace App\Validator;

use App\Entity\Evento;
use App\Repository\EventoRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class LimiteDestaquesValidator extends ConstraintValidator
{
    public function __construct(private EventoRepository $eventos)
    {
    }

    public function validate(mixed $evento, Constraint $constraint): void
    {
        if (!$constraint instanceof LimiteDestaques) {
            throw new UnexpectedTypeException($constraint, LimiteDestaques::class);
        }

        // Só conta se o evento está sendo destacado e ainda não terminou.
        if (!$evento instanceof Evento || !$evento->isDestaque() || !$evento->getInicio() || $evento->jaPassou()) {
            return;
        }

        $outros = $this->eventos->destaquesVigentes(excetoId: $evento->getId());

        if (\count($outros) >= Evento::MAX_DESTAQUES) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ max }}', (string) Evento::MAX_DESTAQUES)
                ->setParameter('{{ eventos }}', implode(', ', array_map(fn (Evento $e) => '“'.$e->getTitulo().'”', $outros)))
                ->atPath('destaque')
                ->addViolation();
        }
    }
}
