<?php

declare(strict_types=1);

namespace Sports\Tests\Structure;

use Exception;
use PHPUnit\Framework\TestCase;
use Sports\Output\StructureOutput;
use Sports\Qualify\Target;
use Sports\Structure;

final class PathNodeConverterTest extends TestCase
{

    public function testOnlyRoot(): void
    {
        $pathNodeConverter = new Structure\PathNodeConverter();
        $rootPathNode = $pathNodeConverter->createPathNode('1');
        self::assertInstanceOf(Structure\PathNode::class, $rootPathNode);
        self::assertSame(null, $rootPathNode->getQualifyTarget());
        self::assertSame(1, $rootPathNode->getQualifyGroupNumber());
        self::assertFalse($rootPathNode->hasPrevious());
    }

    public function testEmpty(): void
    {
        $pathNodeConverter = new Structure\PathNodeConverter();
        $rootPathNode = $pathNodeConverter->createPathNode('');
        self::assertNull($rootPathNode);
    }

    public function testEmptyInvalid(): void
    {
        $pathNodeConverter = new Structure\PathNodeConverter();
        self::expectException(Exception::class);
        $pathNodeConverter->createPathNode('ASD');
    }

    public function test3Rounds(): void
    {
        $pathNodeConverter = new Structure\PathNodeConverter();
        $leafPathNode = $pathNodeConverter->createPathNode('1W2L3');
        self::assertInstanceOf(Structure\PathNode::class, $leafPathNode);
        self::assertSame(Target::Losers, $leafPathNode->getQualifyTarget());
        $middlePathNode = $leafPathNode->getPrevious();
        self::assertInstanceOf(Structure\PathNode::class, $middlePathNode);
        self::assertSame(Target::Winners, $middlePathNode->getQualifyTarget());
        $rootPathNode = $middlePathNode->getPrevious();
        self::assertInstanceOf(Structure\PathNode::class, $rootPathNode);
        self::assertSame(null, $rootPathNode->getQualifyTarget());
        self::assertNull($rootPathNode->getPrevious());
    }

    public function testMoreThan9QualifyGroups(): void
    {
        $pathNodeConverter = new Structure\PathNodeConverter();
        $leafPathNode = $pathNodeConverter->createPathNode('11W12L13');
        self::assertInstanceOf(Structure\PathNode::class, $leafPathNode);
        self::assertSame(Target::Losers, $leafPathNode->getQualifyTarget());
        self::assertSame(13, $leafPathNode->getQualifyGroupNumber());
        $middlePathNode = $leafPathNode->getPrevious();
        self::assertInstanceOf(Structure\PathNode::class, $middlePathNode);
        self::assertSame(Target::Winners, $middlePathNode->getQualifyTarget());
        self::assertSame(12, $middlePathNode->getQualifyGroupNumber());
        $rootPathNode = $middlePathNode->getPrevious();
        self::assertInstanceOf(Structure\PathNode::class, $rootPathNode);
        self::assertSame(null, $rootPathNode->getQualifyTarget());
        self::assertSame(11, $rootPathNode->getQualifyGroupNumber());
        self::assertNull($rootPathNode->getPrevious());
    }
}
