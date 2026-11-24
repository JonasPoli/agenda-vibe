<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * No máximo Evento::MAX_DESTAQUES eventos em destaque ao mesmo tempo
 * (contando só os que ainda não terminaram).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class LimiteDestaques extends Constraint
{
    public string $message = 'Já existem {{ max }} eventos em destaque: {{ eventos }}. Tire um deles do destaque antes de destacar este.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
