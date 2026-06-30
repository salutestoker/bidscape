<?php

namespace App\Services;

final class MoneyCalculator
{
    public static function multiplyDecimalByCents(string|int|float $quantity, int $cents): int
    {
        $value = bcmul((string) $quantity, (string) $cents, 3);

        return (int) bcadd($value, '0.5', 0);
    }

    public static function percent(int $amountCents, int $basisPoints): int
    {
        return intdiv(($amountCents * $basisPoints) + 5000, 10000);
    }

    public static function priceForMargin(int $directCostCents, int $overheadCents, int $targetMarginBasisPoints): int
    {
        $base = $directCostCents + $overheadCents;
        $denominator = 10000 - $targetMarginBasisPoints;

        if ($denominator <= 0) {
            return $base;
        }

        return intdiv(($base * 10000) + $denominator - 1, $denominator);
    }

    public static function priceForMarkup(int $costCents, int $markupBasisPoints): int
    {
        return $costCents + self::percent($costCents, $markupBasisPoints);
    }

    public static function marginBasisPoints(int $sellingPriceCents, int $directCostCents, int $overheadCents): int
    {
        if ($sellingPriceCents <= 0) {
            return 0;
        }

        return max(0, intdiv(($sellingPriceCents - $directCostCents - $overheadCents) * 10000, $sellingPriceCents));
    }
}
