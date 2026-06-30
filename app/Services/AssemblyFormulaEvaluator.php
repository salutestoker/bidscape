<?php

namespace App\Services;

use InvalidArgumentException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class AssemblyFormulaEvaluator
{
    private const ALLOWED_VARIABLES = [
        'quantity',
        'waste_factor',
        'base_depth',
        'production_rate',
        'coefficient',
        'unit_cost',
    ];

    public function __construct(private readonly ExpressionLanguage $expressions = new ExpressionLanguage) {}

    /**
     * @param  array<string, int|float|string>  $variables
     */
    public function evaluate(string $formula, array $variables): float
    {
        $this->validate($formula);

        $scoped = array_intersect_key($variables, array_flip(self::ALLOWED_VARIABLES));

        return (float) $this->expressions->evaluate($formula, $scoped);
    }

    public function validate(string $formula): void
    {
        if (! preg_match('/^[0-9a-zA-Z_+\-*\/().\s]+$/', $formula)) {
            throw new InvalidArgumentException('Formula contains unsupported characters.');
        }

        preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*/', $formula, $matches);

        foreach ($matches[0] as $name) {
            if (! in_array($name, self::ALLOWED_VARIABLES, true)) {
                throw new InvalidArgumentException("Formula variable [{$name}] is not allowed.");
            }
        }

        $this->expressions->lint($formula, self::ALLOWED_VARIABLES);
    }
}
