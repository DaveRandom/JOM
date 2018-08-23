<?php

use DaveRandom\Jom\Document;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;
use DaveRandom\Jom\Node;
use DaveRandom\Jom\PointerGenerator;
use DaveRandom\Jom\VectorNode;

require __DIR__ . '/../vendor/autoload.php';

$json = <<<JSON
{
  "hello": "world",
  "bar": ["what","up","dawg"],
  "baz": {
    "0": "quu",
    "qux": {
      "0": "apple",
      "1": "banana",
      "muffin": ["blueberry","raspberry"]
    }
  }
}
JSON;

interface PathGenerator
{
    function generatePath(Node $node): string;
}

class JsonPointerPathGenerator implements PathGenerator
{
    private $pointerGenerator;

    public function __construct(PointerGenerator $pointerGenerator)
    {
        $this->pointerGenerator = $pointerGenerator;
    }

    /**
     * @throws InvalidSubjectNodeException
     */
    public function generatePath(Node $node): string
    {
        return (string)$this->pointerGenerator->generateAbsolutePointer($node);
    }
}

class JavascriptVarDerefPathGenerator implements PathGenerator
{
    public function generatePath(Node $node): string
    {
        $parts = [];

        $parent = $node->getParent();

        while ($parent && $parent->getParent()) {
            $parts[] = \json_encode($node->getKey());

            $node = $parent;
            $parent = $node->getParent();
        }

        // validating JS identifiers is ridiculous but you should probably do that here
        $result = (string)$node->getKey();

        if ($parts) {
            $result .= '[' . \implode('][', \array_reverse($parts)) . ']';
        }

        return $result;
    }
}

function h($value)
{
    return \htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function recurse(VectorNode $node, PathGenerator $pathGenerator, int $indentSize = 0, int $indentStep = 2)
{
    $indent = str_repeat(' ', $indentSize);
    $innerIndent = str_repeat(' ', $indentSize + $indentStep);

    echo "{$indent}<ul>\n";

    foreach ($node as $child) {
        echo $innerIndent . '<li data-path="'. h($pathGenerator->generatePath($child)) . '">';

        if ($child instanceof VectorNode) {
            echo "\n";
            recurse($child, $pathGenerator, $indentSize + ($indentStep * 2), $indentStep);
            echo "{$innerIndent}</li>\n";
        } else {
            echo h($child->getValue()) . "</li>\n";
        }
    }

    echo "{$indent}</ul>\n";
}

try {
    $root = Document::parse($json)->getRootNode();

    if (!$root instanceof VectorNode) {
        throw new \Exception("Root node of tree must be an array or object");
    }

    echo "<!-- JSON pointers -->\n";
    recurse($root, new JsonPointerPathGenerator(new PointerGenerator($root)));

    echo "<!-- Javascript variable access -->\n";
    recurse($root, new JavascriptVarDerefPathGenerator);
} catch (\Throwable $e) {
    echo $e;
}
