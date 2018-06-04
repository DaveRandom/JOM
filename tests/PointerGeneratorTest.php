<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\ParseFailureException;
use PHPUnit\Framework\TestCase;

class PointerGeneratorTest extends TestCase
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
        $evaluator = new PointerGenerator($doc);

        $this->assertSame($doc->getRootNode(), $evaluator->getRootNode());
    }

    public function testPassingNodeToCtorSucceeds()
    {
        $doc = $this->loadDocument();
        $evaluator = new PointerGenerator($doc->getRootNode());

        $this->assertSame($doc->getRootNode(), $evaluator->getRootNode());
    }

    /**
     * @expectedException \DaveRandom\Jom\Exceptions\InvalidReferenceNodeException
     */
    public function testPassingInvalidValueToCtorFails()
    {
        new PointerGenerator(null);
    }

    public function testGenerateAbsolutePointerRootSelfRef()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root;
        $expected = '';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateAbsolutePointer($target));
    }

    public function testGenerateAbsolutePointerLevelOne()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root['glossary'];
        $expected = '/glossary';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateAbsolutePointer($target));
    }

    public function testGenerateAbsolutePointerLevelTwo()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root['glossary']['title'];
        $expected = '/glossary/title';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateAbsolutePointer($target));
    }

    public function testGenerateAbsolutePointerDeep()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['GlossDef']['GlossSeeAlso'][1];
        $expected = '/glossary/GlossDiv/GlossList/GlossEntry/GlossDef/GlossSeeAlso/1';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateAbsolutePointer($target));
    }

    public function testGenerateRelativePointerRootSelfRef()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root;
        $base = $root;
        $expected = '0';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateRelativePointer($target, $base));
    }

    public function testGenerateRelativePointerParentLevel1()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root;
        $base = $root['glossary'];
        $expected = '1';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateRelativePointer($target, $base));
    }

    public function testGenerateRelativePointerChildLevel1()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root['glossary'];
        $base = $root;
        $expected = '0/glossary';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateRelativePointer($target, $base));
    }

    public function testGenerateRelativePointerParentLevel2()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root;
        $base = $root['glossary']['title'];
        $expected = '2';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateRelativePointer($target, $base));
    }

    public function testGenerateRelativePointerChildLevel2()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root['glossary']['title'];
        $base = $root;
        $expected = '0/glossary/title';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateRelativePointer($target, $base));
    }

    public function testGenerateRelativePointerComplex1()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root['glossary']['title'];
        $base = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['ID'];
        $expected = '4/title';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateRelativePointer($target, $base));
    }

    public function testGenerateRelativePointerComplex2()
    {
        $root = $this->loadDocument()->getRootNode();

        $target = $root['glossary']['GlossDiv']['GlossList']['GlossEntry']['ID'];
        $base = $root['glossary']['title'];
        $expected = '1/GlossDiv/GlossList/GlossEntry/ID';

        $this->assertSame($expected, (string)(new PointerGenerator($root))->generateRelativePointer($target, $base));
    }
}
