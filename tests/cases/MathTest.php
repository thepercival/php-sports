<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:27
 */

namespace Sports\Tests;

use Sports\Math as VoetbalMath;

class MathTest extends \PHPUnit\Framework\TestCase
{
    public function testFaculty()
    {
        $math = new VoetbalMath();

        self::assertSame($math->faculty(0), 1.0);
        self::assertSame($math->faculty(1), 1.0);
        self::assertSame($math->faculty(2), 2.0);
        self::assertSame($math->faculty(3), 6.0);
        self::assertSame($math->faculty(4), 24.0);
        self::assertSame($math->faculty(5), 120.0);
    }

    public function testAbove()
    {
        $math = new VoetbalMath();

        // bijv. aantal wedstrijden per poule(dit is zonder volgorde)
        self::assertSame($math->above(1, 2), 0);
        self::assertSame($math->above(2, 2), 1);
        self::assertSame($math->above(3, 2), 3);
        self::assertSame($math->above(4, 2), 6);
        self::assertSame($math->above(5, 2), 10);
        self::assertSame($math->above(6, 2), 15);

        // bijv. berekening van 5 deelnemers, per avond 3 deelnemers die een halve competitie doen
        self::assertSame($math->above(5, 3), 10);

        self::assertSame($math->above(6, 3), 20);
        self::assertSame($math->above(7, 3), 35);
    }

    public function testGetDivisors()
    {
        $math = new VoetbalMath();

        self::assertSame($math->getDivisors(1), [1]);
        self::assertSame($math->getDivisors(2), [1,2]);
        self::assertSame($math->getDivisors(3), [1,3]);
        self::assertSame($math->getDivisors(4), [1,2,4]);
        self::assertSame($math->getDivisors(5), [1,5]);
        self::assertSame($math->getDivisors(6), [1,2,3,6]);
        self::assertSame($math->getDivisors(7), [1,7]);
        self::assertSame($math->getDivisors(8), [1,2,4,8]);
        self::assertSame($math->getDivisors(9), [1,3,9]);
    }

    public function testGetCommonDivisors()
    {
        $math = new VoetbalMath();

        self::assertSame($math->getCommonDivisors(1, 1), [1]);
        // bijv 2 poules met dezelfde aantal deelnemers, 2 scheidsrecchters
        self::assertSame($math->getCommonDivisors(2, 2), [2,1]);
        self::assertSame($math->getCommonDivisors(2, 1), [1]);
        self::assertSame($math->getCommonDivisors(9, 6), [3,1]);
        self::assertSame($math->getCommonDivisors(8, 4), [4,2,1]);
    }

    public function testGetGreatestCommonDivisor()
    {
        $math = new VoetbalMath();

        self::assertSame($math->getGreatestCommonDivisor([]), 0);
        self::assertSame($math->getGreatestCommonDivisor([1]), 1);
        self::assertSame($math->getGreatestCommonDivisor([2]), 2);
        self::assertSame($math->getGreatestCommonDivisor([8,4]), 4);
        self::assertSame($math->getGreatestCommonDivisor([2, 8,4]), 2);
        self::assertSame($math->getGreatestCommonDivisor([2, 8,1]), 1);

        self::assertSame($math->getGreatestCommonDivisor([15, 18]), 3);
    }
}
