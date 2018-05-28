<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidKeyException;

class ArrayNodeTest extends \PHPUnit\Framework\TestCase
{
    private function assertThrows(string $className, callable $callback)
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            $this->assertInstanceOf($className, $e);
            return;
        }

        $this->fail('Failed asserting that callback throws ' . $className);
    }

    public function testPushUpdatesFirstChild()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($array->getFirstChild());

        $array->push($child1);

        $this->assertSame($child1, $array->getFirstChild());

        $array->push($child2);

        $this->assertSame($child1, $array->getFirstChild());
    }

    public function testPushUpdatesLastChild()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($array->getLastChild());

        $array->push($child1);

        $this->assertSame($child1, $array->getLastChild());

        $array->push($child2);

        $this->assertSame($child2, $array->getLastChild());
    }

    public function testPushedItemsAreAccessibleByIndex()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(0);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[0];
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });

        $array->push($child1);

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });

        $array->push($child2);

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertSame($child2, $array->offsetGet(1));
        $this->assertSame($child2, $array[1]);
    }

    public function testPushSetsChildParent()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($child1->getParent());
        $this->assertNull($child2->getParent());

        $array->push($child1);

        $this->assertSame($array, $child1->getParent());
        $this->assertNull($child2->getParent());

        $array->push($child2);

        $this->assertSame($array, $child1->getParent());
        $this->assertSame($array, $child2->getParent());
    }

    public function testPushSetsChildKey()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($child1->getKey());
        $this->assertNull($child2->getKey());

        $array->push($child1);

        $this->assertSame(0, $child1->getKey());
        $this->assertNull($child2->getKey());

        $array->push($child2);

        $this->assertSame(0, $child1->getKey());
        $this->assertSame(1, $child2->getKey());
    }

    public function testPushSetsChildPreviousSibling()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());

        $array->push($child1);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());

        $array->push($child2);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertSame($child1, $child2->getPreviousSibling());
    }

    public function testPushSetsChildNextSibling()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->push($child1);

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->push($child2);

        $this->assertSame($child2, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
    }

    public function testPopReturnValue()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child2, $array->pop());
        $this->assertSame($child1, $array->pop());
        $this->assertNull($array->pop());
    }

    public function testPopUpdatesFirstChild()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child1, $array->getFirstChild());

        $array->pop();

        $this->assertSame($child1, $array->getFirstChild());

        $array->pop();

        $this->assertNull($array->getFirstChild());
    }

    public function testPopUpdatesLastChild()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child2, $array->getLastChild());

        $array->pop();

        $this->assertSame($child1, $array->getLastChild());

        $array->pop();

        $this->assertNull($array->getLastChild());
    }

    public function testPoppedItemsAreNoLongerAccessibleByIndex()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertSame($child2, $array->offsetGet(1));
        $this->assertSame($child2, $array[1]);

        $array->pop();

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });

        $array->pop();

        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(0);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[0];
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });
    }

    public function testPopClearsChildParent()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($array, $child1->getParent());
        $this->assertSame($array, $child2->getParent());

        $array->pop();

        $this->assertSame($array, $child1->getParent());
        $this->assertNull($child2->getParent());

        $array->pop();

        $this->assertNull($child1->getParent());
        $this->assertNull($child2->getParent());
    }

    public function testPopClearsChildKey()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame(0, $child1->getKey());
        $this->assertSame(1, $child2->getKey());

        $array->pop();

        $this->assertSame(0, $child1->getKey());
        $this->assertNull($child2->getKey());

        $array->pop();

        $this->assertNull($child1->getKey());
        $this->assertNull($child2->getKey());
    }

    public function testPopClearsChildPreviousSibling()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertSame($child1, $child2->getPreviousSibling());

        $array->pop();

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());
    }

    public function testPopClearsChildNextSibling()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child2, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->pop();

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
    }

    public function testUnshiftUpdatesFirstChild()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($array->getFirstChild());

        $array->unshift($child1);

        $this->assertSame($child1, $array->getFirstChild());

        $array->unshift($child2);

        $this->assertSame($child2, $array->getFirstChild());
    }

    public function testUnshiftUpdatesLastChild()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($array->getLastChild());

        $array->unshift($child1);

        $this->assertSame($child1, $array->getLastChild());

        $array->unshift($child2);

        $this->assertSame($child1, $array->getLastChild());
    }

    public function testUnshiftedItemsAreAccessibleByIndex()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(0);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[0];
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });

        $array->unshift($child1);

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });

        $array->unshift($child2);

        $this->assertSame($child2, $array->offsetGet(0));
        $this->assertSame($child2, $array[0]);
        $this->assertSame($child1, $array->offsetGet(1));
        $this->assertSame($child1, $array[1]);
    }

    public function testUnshiftSetsChildParent()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($child1->getParent());
        $this->assertNull($child2->getParent());

        $array->unshift($child1);

        $this->assertSame($array, $child1->getParent());
        $this->assertNull($child2->getParent());

        $array->unshift($child2);

        $this->assertSame($array, $child1->getParent());
        $this->assertSame($array, $child2->getParent());
    }

    public function testUnshiftSetsChildKey()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($child1->getKey());
        $this->assertNull($child2->getKey());

        $array->unshift($child1);

        $this->assertSame(0, $child1->getKey());
        $this->assertNull($child2->getKey());

        $array->unshift($child2);

        $this->assertSame(1, $child1->getKey());
        $this->assertSame(0, $child2->getKey());
    }

    public function testUnshiftSetsChildPreviousSibling()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());

        $array->unshift($child1);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());

        $array->unshift($child2);

        $this->assertSame($child2, $child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());
    }

    public function testUnshiftSetsChildNextSibling()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->unshift($child1);

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->unshift($child2);

        $this->assertNull($child1->getNextSibling());
        $this->assertSame($child1, $child2->getNextSibling());
    }

    public function testShiftReturnValue()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child1, $array->shift());
        $this->assertSame($child2, $array->shift());
        $this->assertNull($array->shift());
    }

    public function testShiftUpdatesFirstChild()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child1, $array->getFirstChild());

        $array->shift();

        $this->assertSame($child2, $array->getFirstChild());

        $array->shift();

        $this->assertNull($array->getFirstChild());
    }

    public function testShiftUpdatesLastChild()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child2, $array->getLastChild());

        $array->shift();

        $this->assertSame($child2, $array->getLastChild());

        $array->shift();

        $this->assertNull($array->getLastChild());
    }

    public function testShiftedItemsAreNoLongerAccessibleByIndex()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertSame($child2, $array->offsetGet(1));
        $this->assertSame($child2, $array[1]);

        $array->shift();

        $this->assertSame($child2, $array->offsetGet(0));
        $this->assertSame($child2, $array[0]);
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });

        $array->shift();

        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(0);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[0];
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });
    }

    public function testShiftClearsChildParent()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($array, $child1->getParent());
        $this->assertSame($array, $child2->getParent());

        $array->shift();

        $this->assertNull($child1->getParent());
        $this->assertSame($array, $child2->getParent());

        $array->shift();

        $this->assertNull($child1->getParent());
        $this->assertNull($child2->getParent());
    }

    public function testShiftClearsChildKey()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame(0, $child1->getKey());
        $this->assertSame(1, $child2->getKey());

        $array->shift();

        $this->assertNull($child1->getKey());
        $this->assertSame(0, $child2->getKey());

        $array->shift();

        $this->assertNull($child1->getKey());
        $this->assertNull($child2->getKey());
    }

    public function testShiftClearsChildPreviousSibling()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertSame($child1, $child2->getPreviousSibling());

        $array->shift();

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());
    }

    public function testShiftClearsChildNextSibling()
    {
        $array = new ArrayNode();
        $child1 = new NullNode();
        $child2 = new NullNode();

        $array->push($child1);
        $array->push($child2);

        $this->assertSame($child2, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->shift();

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
    }

    /*
    public function testUnshift()
    {
        // todo
    }

    public function testShift()
    {
        // todo
    }

    public function testInsert()
    {
        // todo
    }

    public function testRemove()
    {
        // todo
    }

    public function testReplace()
    {
        // todo
    }

    public function testGetValue()
    {
        // todo
    }

    public function testToArray()
    {
        // todo
    }

    public function testOffsetGet()
    {
        // todo
    }

    public function testOffsetSet()
    {
        // todo
    }
    */
}
