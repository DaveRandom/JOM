<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\ParseFailureException;
use PHPUnit\Framework\TestCase;

class PointerEvaluatorTest extends TestCase
{
    /**
     * @return Document
     * @throws ParseFailureException
     */
    private function loadDocument(): Document
    {
        return Document::parse(\file_get_contents(__DIR__ . '/fixtures/glossary.json'));
    }

    public function testPassingDocToCtorSucceeds()
    {
        $doc = $this->loadDocument();
        $evaluator = new PointerEvaluator($doc);

        $this->assertSame($doc->getRootNode(), $evaluator->getRootNode());
    }

    public function testPassingNodeToCtorSucceeds()
    {
        $doc = $this->loadDocument();
        $evaluator = new PointerEvaluator($doc->getRootNode());

        $this->assertSame($doc->getRootNode(), $evaluator->getRootNode());
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidReferenceNodeException
     */
    public function testPassingInvalidValueToCtorFails()
    {
        new PointerEvaluator(null);
    }

    public function testEvaluateAbsolutePointerRootNode()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '';
        $expectedNode = $root;
        $expectedKey = null;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateAbsolutePointerLevelOneExistingNode()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '/glossary';
        $expectedNode = $root['glossary'];
        $expectedKey = 'glossary';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateAbsolutePointerLevelOneMissingNode()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '/missing';

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer);
    }

    public function testEvaluateAbsolutePointerLevelTwoExistingNode()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '/glossary/title';
        $expectedNode = $root['glossary']['title'];
        $expectedKey = 'title';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateAbsolutePointerLevelTwoMissingNode()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '/glossary/missing';

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer);
    }

    public function testEvaluateAbsolutePointerDeepExistingNode()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '/glossary/GlossDiv/GlossList/GlossEntry/GlossDef/GlossSeeAlso/1';
        $expectedNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedKey = 1;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateAbsolutePointerDeepMissingNode()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '/glossary/GlossDiv/GlossList/GlossEntry/GlossDef/GlossSeeAlso/2';

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer);
    }

    public function testEvaluateRelativePointerRootPrefixOnly0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0';
        $baseNode = $root;
        $expectedNode = $root;
        $expectedKey = null;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerRootPrefixOnly1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1';
        $baseNode = $root;

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerLevelOnePrefixOnly0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0';
        $baseNode = $root['glossary'];
        $expectedNode = $root['glossary'];
        $expectedKey = 'glossary';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerLevelOnePrefixOnly1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1';
        $baseNode = $root['glossary'];
        $expectedNode = $root;
        $expectedKey = null;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerLevelOnePrefixOnly2()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '2';
        $baseNode = $root['glossary'];

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerLevelTwoPrefixOnly0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0';
        $baseNode = $root['glossary']['title'];
        $expectedNode = $root['glossary']['title'];
        $expectedKey = 'title';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerLevelTwoPrefixOnly1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1';
        $baseNode = $root['glossary']['title'];
        $expectedNode = $root['glossary'];
        $expectedKey = 'glossary';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerLevelTwoPrefixOnly2()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '2';
        $baseNode = $root['glossary']['title'];
        $expectedNode = $root;
        $expectedKey = null;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerLevelTwoPrefixOnly3()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '3';
        $baseNode = $root['glossary']['title'];

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerDeepPrefixOnly0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedKey = 1;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerDeepPrefixOnly1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'];
        $expectedKey = 'GlossSeeAlso';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerDeepPrefixOnly2()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '2';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef'];
        $expectedKey = 'GlossDef';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerDeepPrefixOnlyToRoot()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '7';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedNode = $root;
        $expectedKey = null;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerDeepPrefixOnlyPastRoot()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '8';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerRootPrefixOnlyKeyLookup0()
    {
        $root = $this->loadDocument()->getRootNode();

        $pointer = '0#';
        $baseNode = $root;
        $expectedKey = null;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerRootPrefixOnlyKeyLookup1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1#';
        $baseNode = $root;

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerLevelOnePrefixOnlyKeyLookup0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0#';
        $baseNode = $root['glossary'];
        $expectedKey = 'glossary';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    public function testEvaluateRelativePointerLevelOnePrefixOnlyKeyLookup1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1#';
        $baseNode = $root['glossary'];
        $expectedKey = null;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerLevelOnePrefixOnlyKeyLookup2()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '2#';
        $baseNode = $root['glossary'];

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerLevelTwoPrefixOnlyKeyLookup0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0#';
        $baseNode = $root['glossary']['title'];
        $expectedKey = 'title';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    public function testEvaluateRelativePointerLevelTwoPrefixOnlyKeyLookup1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1#';
        $baseNode = $root['glossary']['title'];
        $expectedKey = 'glossary';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    public function testEvaluateRelativePointerLevelTwoPrefixOnlyKeyLookup2()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '2#';
        $baseNode = $root['glossary']['title'];
        $expectedKey = null;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerLevelTwoPrefixOnlyKeyLookup3()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '3#';
        $baseNode = $root['glossary']['title'];

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerDeepPrefixOnlyKeyLookup0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0#';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedKey = 1;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    public function testEvaluateRelativePointerDeepPrefixOnlyKeyLookup1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1#';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedKey = 'GlossSeeAlso';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    public function testEvaluateRelativePointerDeepPrefixOnlyKeyLookup2()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '2#';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedKey = 'GlossDef';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    public function testEvaluateRelativePointerDeepPrefixOnlyKeyLookupToRoot()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '7#';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedKey = null;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedKey, $result);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerDeepPrefixOnlyKeyLookupPastRoot()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '8#';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerRootPrefixAndPath0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0/glossary/title';
        $baseNode = $root;
        $expectedNode = $root['glossary']['title'];
        $expectedKey = 'title';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerRootPrefixAndPath1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1/glossary/title';
        $baseNode = $root;

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerLevelOnePrefixAndPath0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0/title';
        $baseNode = $root['glossary'];
        $expectedNode = $root['glossary']['title'];
        $expectedKey = 'title';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerLevelOnePrefixAndPath1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1/glossary/title';
        $baseNode = $root['glossary'];
        $expectedNode = $root['glossary']['title'];
        $expectedKey = 'title';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerLevelOnePrefixAndPath2()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '2/glossary/title';
        $baseNode = $root['glossary'];

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerLevelTwoPrefixAndPath0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0/GlossList';
        $baseNode = $root['glossary']['GlossDiv'];
        $expectedNode = $root['glossary']['GlossDiv']['GlossList'];
        $expectedKey = 'GlossList';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerLevelTwoPrefixAndPath1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1/GlossDiv/GlossList';
        $baseNode = $root['glossary']['GlossDiv'];
        $expectedNode = $root['glossary']['GlossDiv']['GlossList'];
        $expectedKey = 'GlossList';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerLevelTwoPrefixAndPath2()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '2/glossary/GlossDiv/GlossList';
        $baseNode = $root['glossary']['GlossDiv'];
        $expectedNode = $root['glossary']['GlossDiv']['GlossList'];
        $expectedKey = 'GlossList';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerLevelTwoPrefixAndPath3()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '3/glossary/GlossDiv/GlossList';
        $baseNode = $root['glossary']['title'];

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }

    public function testEvaluateRelativePointerDeepPrefixAndPath0()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '0/1';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'];
        $expectedNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedKey = 1;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerDeepPrefixAndPath1()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '1/0';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][0];
        $expectedKey = 0;

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerDeepPrefixAndPath2()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '2/para';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['para'];
        $expectedKey = 'para';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    public function testEvaluateRelativePointerDeepPrefixAndPathToRoot()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '7/glossary/title';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expectedNode = $root['glossary']['title'];
        $expectedKey = 'title';

        $result = (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);

        $this->assertSame($expectedNode, $result);
        $this->assertSame($expectedNode->getKey(), $expectedKey);
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException
     */
    public function testEvaluateRelativePointerDeepPrefixAndPathPastRoot()
    {
        $root = $this->loadDocument()->getRootNode();

        /** @var Node $expectedNode */
        $pointer = '8/glossary/title';
        $baseNode = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];

        (new PointerEvaluator($root))
            ->evaluatePointer($pointer, $baseNode);
    }
}
