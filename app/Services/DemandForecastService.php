<?php

namespace App\Services;

use App\Models\DemandObservation;
use App\Models\Route;

class DemandForecastService
{
    private const MIN_OBSERVATIONS = 30;

    /**
     * d = a*p + b*q + c için En Küçük Kareler regresyonu.
     * Yetersiz veri varsa null döner.
     *
     * @return array{a: float, b: float, c: float}|null
     */
    public function calculateCoefficients(Route $route, string $cabinClass): ?array
    {
        $observations = DemandObservation::where('route_id', $route->id)
            ->where('cabin_class', $cabinClass)
            ->get();

        if ($observations->count() < self::MIN_OBSERVATIONS) {
            return null;
        }

        $n = $observations->count();
        $sumP = $sumQ = $sumD = 0.0;
        $sumPP = $sumQQ = $sumPQ = 0.0;
        $sumPD = $sumQD = 0.0;

        foreach ($observations as $obs) {
            $p = (float) $obs->price;
            $q = (float) $obs->capacity_remaining;
            $d = (float) $obs->seats_sold;

            $sumP  += $p;
            $sumQ  += $q;
            $sumD  += $d;
            $sumPP += $p * $p;
            $sumQQ += $q * $q;
            $sumPQ += $p * $q;
            $sumPD += $p * $d;
            $sumQD += $q * $d;
        }

        // Normal denklem sistemi: M * [a, b, c]^T = R
        $m = [
            [$sumPP, $sumPQ, $sumP],
            [$sumPQ, $sumQQ, $sumQ],
            [$sumP,  $sumQ,  $n],
        ];
        $r = [$sumPD, $sumQD, $sumD];

        $detM = $this->determinant3x3($m);

        if (abs($detM) < 1e-9) {
            return null; // matris tekil, çözüm yok
        }

        return [
            'a' => $this->determinant3x3($this->replaceColumn($m, 0, $r)) / $detM,
            'b' => $this->determinant3x3($this->replaceColumn($m, 1, $r)) / $detM,
            'c' => $this->determinant3x3($this->replaceColumn($m, 2, $r)) / $detM,
        ];
    }

    /** Regresyon güvenilir mi? Kural: a < 0 olmalı (talep fiyatla ters orantılı). */
    public function isReliable(Route $route, string $cabinClass): bool
    {
        $coef = $this->calculateCoefficients($route, $cabinClass);

        return $coef !== null && $coef['a'] < 0;
    }

    /** p* = -(b·q + c) / (2a). Güvenilir değilse null döner. */
    public function calculateOptimalPrice(Route $route, string $cabinClass, int $capacityRemaining): ?float
    {
        $coef = $this->calculateCoefficients($route, $cabinClass);

        if ($coef === null || $coef['a'] >= 0) {
            return null;
        }

        return -($coef['b'] * $capacityRemaining + $coef['c']) / (2 * $coef['a']);
    }

    private function determinant3x3(array $m): float
    {
        return $m[0][0] * ($m[1][1] * $m[2][2] - $m[1][2] * $m[2][1])
            - $m[0][1] * ($m[1][0] * $m[2][2] - $m[1][2] * $m[2][0])
            + $m[0][2] * ($m[1][0] * $m[2][1] - $m[1][1] * $m[2][0]);
    }

    private function replaceColumn(array $matrix, int $col, array $newCol): array
    {
        foreach ($matrix as $i => $row) {
            $row[$col] = $newCol[$i];
            $matrix[$i] = $row;
        }

        return $matrix;
    }
}
