<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-1-17
 * Time: 21:56
 */

namespace Sports\Tests;

use \Sports\Association as Association;

class AssociationTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateNameMin()
    {
        $this->expectException(\InvalidArgumentException::class);
        $association = new Association("");
    }

    public function testCreateNameMax()
    {
        $this->expectException(\InvalidArgumentException::class);
        $associationName = new Association("123456789012345678901");
    }

    public function testCreateNameCharactersOne()
    {
        $this->expectException(\InvalidArgumentException::class);
        $association = new Association("-");
    }

    public function testCreateNameCharactersTwo()
    {
        $association = new Association("KNVB");
        $this->assertSame("KNVB", $association->getName());
    }

    public function testCreateDescriptionMin()
    {
        $association = new Association("KNVB");
        $association->setDescription("");
        $this->assertNull($association->getDescription());
    }

    public function testCreateDescriptionMax()
    {
        $association = new Association("KNVB");
        $this->expectException(\InvalidArgumentException::class);
        $association->setDescription("123456789012345678901234567890123456789012345678901");
    }

    public function testCreate()
    {
        $association = new Association("KNVB");
        $this->assertNotEquals(null, $association);
    }

    public function testParentChildSame()
    {
        $this->expectException(\Exception::class);
        $association = new Association("KNVB");
        $association->setParent($association);
    }

    public function testParentChildNewParent()
    {
        $parent = new Association("parent");
        $child = new Association("child");
        $child->setParent($parent);
        $this->assertCount(1, $parent->getChildren());
    }

    /*public function testParentChildReplaceParent()
    {
        $oldParent = new Association( new Association\Name("OldParent") );
        $newParent = new Association( new Association\Name("NewParent") );
        $child = new Association( new Association\Name("child") );
        $child->putParent($parent);
        $this->assertNotEquals(1, $parent->children()->count());
    }*/
}
