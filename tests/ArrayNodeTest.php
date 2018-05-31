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

    // region push()

    public function testPushUpdatesFirstChild()
    {
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

        $this->assertNull($array->getFirstChild());

        $array->push($child1);

        $this->assertSame($child1, $array->getFirstChild());

        $array->push($child2);

        $this->assertSame($child1, $array->getFirstChild());
    }

    public function testPushUpdatesLastChild()
    {
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

        $this->assertNull($array->getLastChild());

        $array->push($child1);

        $this->assertSame($child1, $array->getLastChild());

        $array->push($child2);

        $this->assertSame($child2, $array->getLastChild());
    }

    public function testPushedItemsAreAccessibleByIndex()
    {
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

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

    public function testPushMultipleItemsOrder()
    {
        $array = ArrayNode::createFromValue([null]);
        $child1 = new NullNode;
        $child2 = new NullNode;

        $array->push($child1, $child2);

        $this->assertSame($child2, $array->getLastChild());

        $this->assertSame($child1, $array[1]);
        $this->assertSame($child2, $array[2]);

        $this->assertSame(1, $child1->getKey());
        $this->assertSame(2, $child2->getKey());
    }

    public function testPushSetsChildParent()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame($array, $child1->getParent());
        $this->assertNull($child2->getParent());

        $array->push($child2);

        $this->assertSame($array, $child1->getParent());
        $this->assertSame($array, $child2->getParent());
    }

    public function testPushSetsChildKey()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame(0, $child1->getKey());
        $this->assertNull($child2->getKey());

        $array->push($child2);

        $this->assertSame(0, $child1->getKey());
        $this->assertSame(1, $child2->getKey());
    }

    public function testPushSetsChildPreviousSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());

        $array->push($child2);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertSame($child1, $child2->getPreviousSibling());
    }

    public function testPushSetsChildNextSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->push($child2);

        $this->assertSame($child2, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\EmptySubjectNodeListException
     */
    public function testPushNoArgsThrows()
    {
        (new ArrayNode)->push();
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testPushExistingChildThrows()
    {
        $array = new ArrayNode([
            $child = new NullNode,
        ]);

        $array->push($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testPushChildOfOtherNodeThrows()
    {
        $array = new ArrayNode;
        $child = ArrayNode::createFromValue([null])->getFirstChild();

        $array->push($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testPushNodeWithOwnerDocumentInToOrphanedArrayThrows()
    {
        $array = new ArrayNode;
        $child = Document::createFromValue(null)->getRootNode();

        $array->push($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testPushOrphanedNodeInToArrayWithOwnerDocumentThrows()
    {
        /** @var ArrayNode $array */
        $array = Document::createFromValue([])->getRootNode();
        $child = new NullNode;

        $array->push($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testPushNodeWithDifferentOwnerDocumentThrows()
    {
        /** @var ArrayNode $array */
        $array = Document::createFromValue([])->getRootNode();
        $child = new NullNode(Document::createFromValue(null));

        $array->push($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
     */
    public function testPushWithActiveIteratorThrows()
    {
        $array = ArrayNode::createFromValue([null]);
        $child = new NullNode;

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($array as $unused) {
            $array->push($child);
        }
    }

    // endregion

    // region pop()

    public function testPopReturnValue()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertSame($child2, $array->pop());
        $this->assertSame($child1, $array->pop());
        $this->assertNull($array->pop());
    }

    public function testPopUpdatesFirstChild()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertSame($child1, $array->getFirstChild());

        $array->pop();

        $this->assertSame($child1, $array->getFirstChild());

        $array->pop();

        $this->assertNull($array->getFirstChild());
    }

    public function testPopUpdatesLastChild()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertSame($child2, $array->getLastChild());

        $array->pop();

        $this->assertSame($child1, $array->getLastChild());

        $array->pop();

        $this->assertNull($array->getLastChild());
    }

    public function testPoppedItemsAreNoLongerAccessibleByIndex()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

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
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

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
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

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
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertSame($child1, $child2->getPreviousSibling());

        $array->pop();

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());
    }

    public function testPopClearsChildNextSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertSame($child2, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->pop();

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
     */
    public function testPopWithActiveIteratorThrows()
    {
        $array = ArrayNode::createFromValue([null]);

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($array as $unused) {
            $array->pop();
        }
    }

    // endregion

    // region unshift()

    public function testUnshiftUpdatesFirstChild()
    {
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

        $this->assertNull($array->getFirstChild());

        $array->unshift($child1);

        $this->assertSame($child1, $array->getFirstChild());

        $array->unshift($child2);

        $this->assertSame($child2, $array->getFirstChild());
    }

    public function testUnshiftUpdatesLastChild()
    {
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

        $this->assertNull($array->getLastChild());

        $array->unshift($child1);

        $this->assertSame($child1, $array->getLastChild());

        $array->unshift($child2);

        $this->assertSame($child1, $array->getLastChild());
    }

    public function testUnshiftedItemsAreAccessibleByIndex()
    {
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

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

    public function testUnshiftMultipleItemsOrder()
    {
        $array = ArrayNode::createFromValue([null]);
        $child1 = new NullNode;
        $child2 = new NullNode;

        $array->unshift($child1, $child2);

        $this->assertSame($child1, $array->getFirstChild());

        $this->assertSame($child1, $array[0]);
        $this->assertSame($child2, $array[1]);

        $this->assertSame(0, $child1->getKey());
        $this->assertSame(1, $child2->getKey());
    }

    public function testUnshiftSetsChildParent()
    {
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

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
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

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
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

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
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->unshift($child1);

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->unshift($child2);

        $this->assertNull($child1->getNextSibling());
        $this->assertSame($child1, $child2->getNextSibling());
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\EmptySubjectNodeListException
     */
    public function testUnshiftNoArgsThrows()
    {
        (new ArrayNode)->unshift();
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testUnshiftExistingChildThrows()
    {
        $array = new ArrayNode;
        $child = new NullNode;

        $array->unshift($child);
        $array->unshift($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testUnshiftChildOfOtherNodeThrows()
    {
        $array1 = new ArrayNode;
        $array2 = new ArrayNode;
        $child = new NullNode;

        $array1->unshift($child);
        $array2->unshift($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testUnshiftNodeWithOwnerDocumentInToOrphanedArrayThrows()
    {
        $array = new ArrayNode;
        $child = new NullNode(Document::createFromValue(null));

        $array->unshift($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testUnshiftOrphanedNodeInToArrayWithOwnerDocumentThrows()
    {
        /** @var ArrayNode $array */
        $array = Document::createFromValue([])->getRootNode();
        $child = new NullNode;

        $array->unshift($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testUnshiftNodeWithDifferentOwnerDocumentThrows()
    {
        /** @var ArrayNode $array */
        $array = Document::createFromValue([])->getRootNode();
        $child = new NullNode(Document::createFromValue(null));

        $array->unshift($child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
     */
    public function testUnshiftWithActiveIteratorThrows()
    {
        $array = new ArrayNode;
        $child1 = new NullNode;
        $child2 = new NullNode;

        $array->unshift($child1);

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($array as $unused) {
            $array->unshift($child2);
        }
    }

    // endregion

    // region shift()

    public function testShiftReturnValue()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertSame($child1, $array->shift());
        $this->assertSame($child2, $array->shift());
        $this->assertNull($array->shift());
    }

    public function testShiftUpdatesFirstChild()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertSame($child1, $array->getFirstChild());

        $array->shift();

        $this->assertSame($child2, $array->getFirstChild());

        $array->shift();

        $this->assertNull($array->getFirstChild());
    }

    public function testShiftUpdatesLastChild()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertSame($child2, $array->getLastChild());

        $array->shift();

        $this->assertSame($child2, $array->getLastChild());

        $array->shift();

        $this->assertNull($array->getLastChild());
    }

    public function testShiftedItemsAreNoLongerAccessibleByIndex()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

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
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

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
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

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
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertSame($child1, $child2->getPreviousSibling());

        $array->shift();

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());
    }

    public function testShiftClearsChildNextSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);

        $this->assertSame($child2, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->shift();

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
     */
    public function testShiftWithActiveIteratorThrows()
    {
        $array = ArrayNode::createFromValue([null]);

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($array as $unused) {
            $array->shift();
        }
    }

    // endregion

    // region insert()

    public function testInsertUpdatesFirstChildWhenRefNodeIsFirstChild()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame($child1, $array->getFirstChild());

        $array->insert($child2, $child1);

        $this->assertSame($child2, $array->getFirstChild());
    }

    public function testInsertUpdatesLastChildWhenRefNodeIsNull()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame($child1, $array->getLastChild());

        $array->insert($child2, null);

        $this->assertSame($child2, $array->getLastChild());
    }

    public function testInsertedItemsWithRefNodeAreAccessibleByIndex()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame($child1, $array->getLastChild());

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });

        $array->insert($child2, $child1);

        $this->assertSame($child2, $array->offsetGet(0));
        $this->assertSame($child2, $array[0]);
        $this->assertSame($child1, $array->offsetGet(1));
        $this->assertSame($child1, $array[1]);
    }

    public function testInsertedItemsWithNullRefNodeAreAccessibleByIndex()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame($child1, $array->getLastChild());

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            $array->offsetGet(1);
        });
        $this->assertThrows(InvalidKeyException::class, function() use($array) {
            echo $array[1];
        });

        $array->insert($child2, null);

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertSame($child2, $array->offsetGet(1));
        $this->assertSame($child2, $array[1]);
    }

    public function testInsertWithRefNodeSetsChildParent()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame($array, $child1->getParent());
        $this->assertNull($child2->getParent());

        $array->insert($child2, $child1);

        $this->assertSame($array, $child1->getParent());
        $this->assertSame($array, $child2->getParent());
    }

    public function testInsertWithNullRefNodeSetsChildParent()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame($array, $child1->getParent());
        $this->assertNull($child2->getParent());

        $array->insert($child2, null);

        $this->assertSame($array, $child1->getParent());
        $this->assertSame($array, $child2->getParent());
    }

    public function testInsertWithRefNodeSetsChildKey()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame(0, $child1->getKey());
        $this->assertNull($child2->getKey());

        $array->insert($child2, $child1);

        $this->assertSame(1, $child1->getKey());
        $this->assertSame(0, $child2->getKey());
    }

    public function testInsertWithNullRefNodeSetsChildKey()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame(0, $child1->getKey());
        $this->assertNull($child2->getKey());

        $array->insert($child2, null);

        $this->assertSame(0, $child1->getKey());
        $this->assertSame(1, $child2->getKey());
    }

    public function testInsertWithRefNodeSetsChildPreviousSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());

        $array->insert($child2, $child1);

        $this->assertSame($child2, $child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());
    }

    public function testInsertWithNullRefNodeSetsChildPreviousSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());

        $array->insert($child2, null);

        $this->assertSame($child1, $child2->getPreviousSibling());
        $this->assertNull($child1->getPreviousSibling());
    }

    public function testInsertWithRefNodeSetsChildNextSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->insert($child2, $child1);

        $this->assertSame($child1, $child2->getNextSibling());
        $this->assertNull($child1->getNextSibling());
    }

    public function testInsertWithNullRefNodeSetsChildNextSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());

        $array->insert($child2, null);

        $this->assertSame($child2, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidReferenceNodeException
     */
    public function testInsertWithMissingRefNodeThrows()
    {
        try {
            $array = new ArrayNode;
            $child1 = new NullNode;
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child1, $child2);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertWithDuplicateRefNodeThrows()
    {
        try {
            $array = new ArrayNode([
                $child = new NullNode,
            ]);
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child, $child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertExistingChildWithExistingRefNodeThrows()
    {
        try {
            $array = new ArrayNode([
                $child1 = new NullNode,
                $child2 = new NullNode,
            ]);
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child1, $child2);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertExistingChildWithMissingRefNodeThrows()
    {
        try {
            $array = new ArrayNode([
                $child1 = new NullNode,
            ]);
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child1, $child2);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertExistingChildWithNullRefNodeThrows()
    {
        try {
            $array = new ArrayNode([
                $child = new NullNode,
            ]);
       } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child, null);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertChildOfOtherNodeWithExistingRefNodeThrows()
    {
        try {
            $array = new ArrayNode([
                $child1 = new NullNode,
            ]);
            $child2 = ArrayNode::createFromValue([null])->getFirstChild();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertChildOfOtherNodeWithMissingRefNodeThrows()
    {
        try {
            $array = new ArrayNode;
            $child1 = new NullNode;
            $child2 = ArrayNode::createFromValue([null])->getFirstChild();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertChildOfOtherNodeWithNullRefNodeThrows()
    {
        try {
            $array = new ArrayNode;
            $child = ArrayNode::createFromValue([null])->getFirstChild();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child, null);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertNodeWithOwnerDocumentInToOrphanedArrayWithExistingRefNodeThrows()
    {
        try {
            $array = new ArrayNode([
                $child1 = new NullNode,
            ]);
            $child2 = Document::createFromValue(null)->getRootNode();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertNodeWithOwnerDocumentInToOrphanedArrayWithMissingRefNodeThrows()
    {
        try {
            $array = new ArrayNode;
            $child1 = new NullNode;
            $child2 = Document::createFromValue(null)->getRootNode();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertNodeWithOwnerDocumentInToOrphanedArrayWithNullRefNodeThrows()
    {
        try {
            $array = new ArrayNode;
            $child = Document::createFromValue(null)->getRootNode();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child, null);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertOrphanedNodeInToArrayWithOwnerDocumentWithExistingRefNodeThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([null])->getRootNode();
            $child1 = $array->getLastChild();
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertOrphanedNodeInToArrayWithOwnerDocumentWithMissingRefNodeThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([null])->getRootNode();
            $child1 = $array->pop();
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertOrphanedNodeInToArrayWithOwnerDocumentWithNullRefNodeThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([null])->getRootNode();
            $child = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child, null);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertNodeWithDifferentOwnerDocumentWithExistingRefNodeThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([null])->getRootNode();
            $child1 = $array->getFirstChild();
            $child2 = new NullNode(Document::createFromValue(null));
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertNodeWithDifferentOwnerDocumentWithMissingRefNodeThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([null])->getRootNode();
            $child1 = $array->pop();
            $child2 = new NullNode(Document::createFromValue(null));
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testInsertNodeWithDifferentOwnerDocumentWithNullRefNodeThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([])->getRootNode();
            $child = new NullNode(Document::createFromValue(null));
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->insert($child, null);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
     */
    public function testInsertWithActiveIteratorWithExistingRefNodeThrows()
    {
        try {
            $array = ArrayNode::createFromValue([null]);
            $child1 = $array->getFirstChild();
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($array as $unused) {
            $array->insert($child2, $child1);
        }
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
     */
    public function testInsertWithActiveIteratorWithMissingRefNodeThrows()
    {
        try {
            $array = ArrayNode::createFromValue([null]);
            $child1 = new NullNode;
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($array as $unused) {
            $array->insert($child2, $child1);
        }
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
     */
    public function testInsertWithActiveIteratorWithNullRefNodeThrows()
    {
        try {
            $array = ArrayNode::createFromValue([null]);
            $child = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($array as $unused) {
            $array->insert($child, null);
        }
    }

    // endregion

    // region replace()

    public function testReplaceUpdatesFirstChildWhenRefNodeIsFirstChild()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);
        $child3 = new NullNode;

        $this->assertSame($child1, $array->getFirstChild());
        $this->assertSame($child2, $array->getLastChild());

        $array->replace($child3, $child1);

        $this->assertSame($child3, $array->getFirstChild());
        $this->assertSame($child2, $array->getLastChild());
    }

    public function testReplaceUpdatesLastChildWhenRefNodeIsLastChild()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);
        $child3 = new NullNode;

        $this->assertSame($child1, $array->getFirstChild());
        $this->assertSame($child2, $array->getLastChild());

        $array->replace($child3, $child2);

        $this->assertSame($child1, $array->getFirstChild());
        $this->assertSame($child3, $array->getLastChild());
    }

    public function testReplacedItemsAreAccessibleByIndex()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);
        $child3 = new NullNode;

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertSame($child2, $array->offsetGet(1));
        $this->assertSame($child2, $array[1]);

        $array->replace($child3, $child1);

        $this->assertSame($child3, $array->offsetGet(0));
        $this->assertSame($child3, $array[0]);
        $this->assertSame($child2, $array->offsetGet(1));
        $this->assertSame($child2, $array[1]);

        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);
        $child3 = new NullNode;

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertSame($child2, $array->offsetGet(1));
        $this->assertSame($child2, $array[1]);

        $array->replace($child3, $child2);

        $this->assertSame($child1, $array->offsetGet(0));
        $this->assertSame($child1, $array[0]);
        $this->assertSame($child3, $array->offsetGet(1));
        $this->assertSame($child3, $array[1]);
    }

    public function testReplaceUpdatesChildParent()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame($array, $child1->getParent());
        $this->assertNull($child2->getParent());

        $array->replace($child2, $child1);

        $this->assertNull($child1->getParent());
        $this->assertSame($array, $child2->getParent());
    }

    public function testReplaceUpdatesChildKey()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
        ]);
        $child2 = new NullNode;

        $this->assertSame(0, $child1->getKey());
        $this->assertNull($child2->getKey());

        $array->replace($child2, $child1);

        $this->assertNull($child1->getKey());
        $this->assertSame(0, $child2->getKey());
    }

    public function testReplaceUpdatesChildPreviousSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);
        $child3 = new NullNode;

        $this->assertNull($child1->getPreviousSibling());
        $this->assertSame($child1, $child2->getPreviousSibling());
        $this->assertNull($child3->getPreviousSibling());

        $array->replace($child3, $child1);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertSame($child3, $child2->getPreviousSibling());
        $this->assertNull($child3->getPreviousSibling());

        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);
        $child3 = new NullNode;

        $this->assertNull($child1->getPreviousSibling());
        $this->assertSame($child1, $child2->getPreviousSibling());
        $this->assertNull($child3->getPreviousSibling());

        $array->replace($child3, $child2);

        $this->assertNull($child1->getPreviousSibling());
        $this->assertNull($child2->getPreviousSibling());
        $this->assertSame($child1, $child3->getPreviousSibling());
    }

    public function testReplaceUpdatesChildNextSibling()
    {
        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);
        $child3 = new NullNode;

        $this->assertSame($child2, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
        $this->assertNull($child3->getNextSibling());

        $array->replace($child3, $child1);

        $this->assertNull($child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
        $this->assertSame($child2, $child3->getNextSibling());

        $array = new ArrayNode([
            $child1 = new NullNode,
            $child2 = new NullNode,
        ]);
        $child3 = new NullNode;

        $this->assertSame($child2, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
        $this->assertNull($child3->getNextSibling());

        $array->replace($child3, $child2);

        $this->assertSame($child3, $child1->getNextSibling());
        $this->assertNull($child2->getNextSibling());
        $this->assertNull($child3->getNextSibling());
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidReferenceNodeException
     */
    public function testReplaceMissingNodeThrows()
    {
        try {
            $array = new ArrayNode;
            $child1 = new NullNode;
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child1, $child2);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceExistingNodeWithItselfThrows()
    {
        try {
            $array = new ArrayNode([
                $child = new NullNode,
            ]);
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child, $child);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceExistingNodeWithExistingChildThrows()
    {
        try {
            $array = new ArrayNode([
                $child1 = new NullNode,
                $child2 = new NullNode,
            ]);
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child1, $child2);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceMissingNodeWithExistingChildThrows()
    {
        try {
            $array = new ArrayNode([
                $child1 = new NullNode,
            ]);
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child1, $child2);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceExistingNodeWithChildOfOtherNodeThrows()
    {
        try {
            $array = new ArrayNode([
                $child1 = new NullNode,
            ]);
            $child2 = ArrayNode::createFromValue([null])->getFirstChild();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceMissingNodeWithChildOfOtherNodeThrows()
    {
        try {
            $array = new ArrayNode;
            $child1 = new NullNode;
            $child2 = ArrayNode::createFromValue([null])->getFirstChild();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceExistingNodeInUnownedArrayWithOwnedNodeThrows()
    {
        try {
            $array = new ArrayNode([
                $child1 = new NullNode,
            ]);
            $child2 = Document::createFromValue(null)->getRootNode();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceMissingNodeInUnownedArrayWithOwnedNodeThrows()
    {
        try {
            $array = new ArrayNode;
            $child1 = new NullNode;
            $child2 = Document::createFromValue(null)->getRootNode();
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceExistingNodeInOwnedArrayWithUnownedNodeThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([null])->getRootNode();
            $child1 = $array->getLastChild();
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceMissingNodeInOwnedArrayWithUnownedNodeThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([null])->getRootNode();
            $child1 = $array->pop();
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceExistingNodeInOwnedArrayWithNodeWithDifferentOwnerThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([null])->getRootNode();
            $child1 = $array->getFirstChild();
            $child2 = new NullNode(Document::createFromValue(null));
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
     */
    public function testReplaceMissingNodeInOwnedArrayWithNodeWithDifferentOwnerThrows()
    {
        try {
            /** @var ArrayNode $array */
            $array = Document::createFromValue([null])->getRootNode();
            $child1 = $array->pop();
            $child2 = new NullNode(Document::createFromValue(null));
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        $array->replace($child2, $child1);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
     */
    public function testReplaceExistingNodeWithActiveIteratorThrows()
    {
        try {
            $array = ArrayNode::createFromValue([null]);
            $child1 = $array->getFirstChild();
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($array as $unused) {
            $array->replace($child2, $child1);
        }
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
     */
    public function testReplaceMissingNodeWithActiveIteratorThrows()
    {
        try {
            $array = ArrayNode::createFromValue([null]);
            $child1 = new NullNode;
            $child2 = new NullNode;
        } catch (\Throwable $e) {
            $this->fail("Unexpected error during setup: {$e->getMessage()}");
            return;
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($array as $unused) {
            $array->replace($child2, $child1);
        }
    }

    // endregion

    /*
    public function testRemove()
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
