JOM - JSON Object Model
===

A DOM-like API for working with JSON data, including an [RFC 6901 JSON pointer](https://tools.ietf.org/html/rfc6901)
implementation with the [draft relative JSON pointer](https://tools.ietf.org/html/draft-luff-relative-json-pointer-00)
extension.

API
---

```php
final class \DaveRandom\Jom\Document
    implements \JsonSerializable
{
    /**
     * Create a Document instance from a JSON string, forwarding arguments to json_encode().
     *
     * @throws ParseFailureException when the document creation fails.
     */
    public static function parse(string $json, int $depthLimit = 512, int $options = 0): Document;

    /**
     * Returns the root node of the document, or NULL if the document is empty.
     */
    public function getRootNode(): ?Node;

    /**
     * Evaluate a JSON pointer against the document tree.
     *
     * @throws InvalidPointerException when the supplied pointer string is invalid
     * @throws PointerEvaluationFailureException when the pointer does not indiciate a valid location in the document
     * @throws InvalidSubjectNodeException when the $base node is not part of the document
     */
    public function evaluatePointer(Pointer|string $pointer, Node $base = null): Node|int|string;
}
```

```php
abstract class \DaveRandom\Jom\Node
    implements \JsonSerializable
{
    /**
     * Returns the parent node of this node, or NULL if this is the root node or the node is not present in the owning
     * document.
     */
    public function getParent(): ?Node;

    /**
     * Returns the next sibling node of this node, or NULL if the node does not have a following sibling node.
     */
    public function getPreviousSibling(): ?Node;

    /**
     * Returns the previous sibling node of this node, or NULL if the node does not have a preceding sibling node.
     */
    public function getNextSibling(): ?Node;

    /**
     * Returns TRUE if this node has child nodes, otherwise FALSE.
     */
    public function hasChildren(): bool;

    /**
     * Returns the first child node of this node, or NULL if the node does not have any child nodes.
     */
    public function getFirstChild(): ?Node;

    /**
     * Returns the last child node of this node, or NULL if the node does not have any child nodes.
     */
    public function getLastChild(): ?Node;

    /**
     * Returns the Document object that owns this node.
     */
    public function getOwnerDocument(): Document;

    /**
     * Get a JSON pointer for this node's position in the document. If the $base node is supplied, get a relative
     * pointer.
     *
     * @throws InvalidSubjectNodeException when the $base node is invalid
     */
    public function getPointer(Node $base = null): Pointer;

    /**
     * Returns the key of this node within its parent node, or NULL if this is the root node of the document.
     */
    public function getKey(): string|int|null;

    /**
     * Returns the data represented by this node as the appropriate PHP type.
     */
    public function getValue(): mixed;
}
```

```php
final class \DaveRandom\Jom\BooleanNode extends \DaveRandom\Jom\Node
{
    /**
     * Create a new boolean value node owned by the supplied document.
     */
    public function __construct(Document $ownerDocument, bool $value = false);

    /**
     * Set the value of this node
     */
    public function setValue(bool $value): void;
}
```

```php
final class \DaveRandom\Jom\NullNode extends \DaveRandom\Jom\Node
{
    /**
     * Create a new NULL value node owned by the supplied document.
     */
    public function __construct(Document $ownerDocument);
}
```

```php
final class \DaveRandom\Jom\NumberNode extends \DaveRandom\Jom\Node
{
    /**
     * Create a new number value node owned by the supplied document.
     */
    public function __construct(Document $ownerDocument);

    /**
     * Set the value of this node
     */
    public function setValue(int|float $value): void;
}
```

```php
final class \DaveRandom\Jom\StringNode extends \DaveRandom\Jom\Node
{
    /**
     * Create a new string value node owned by the supplied document.
     */
    public function __construct(Document $ownerDocument);

    /**
     * Set the value of this node
     */
    public function setValue(string $value): void;
}
```

```php
final class \DaveRandom\Jom\StringNode extends \DaveRandom\Jom\Node
{
    /**
     * Create a new string value node owned by the supplied document.
     */
    public function __construct(Document $ownerDocument);

    /**
     * Set the value of this node
     */
    public function setValue(string $value): void;
}
```

```php
abstract class \DaveRandom\Jom\VectorNode extends \DaveRandom\Jom\Node 
    implements \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * Returns the data represented by this node as an array.
     */
    public function toArray(): array;
}
```

```php
abstract class \DaveRandom\Jom\ArrayNode extends \DaveRandom\Jom\VectorNode
{
    /**
     * Append a node to the array.
     *
     * @throws InvalidSubjectNodeException when the new node is not owned by the same document.
     * @throws InvalidOperationException when there is an active iterator for the array.
     */
    public function push(Node $node): void;

    /**
     * Remove the last node from the array, if any, and return it.
     *
     * @throws InvalidOperationException when there is an active iterator for the array.
     */
    public function pop(): ?Node;

    /**
     * Prepend a node to the array.
     *
     * @throws InvalidSubjectNodeException when the new node is not owned by the same document.
     * @throws InvalidOperationException when there is an active iterator for the array.
     */
    public function unshift(Node $node): void;

    /**
     * Remove the first node from the array, if any, and return it.
     *
     * @throws InvalidOperationException when there is an active iterator for the array.
     */
    public function shift(): ?Node;

    /**
     * Insert a new node before the supplied reference node. If the reference node is NULL it is equivalent to push().
     *
     * @throws InvalidOperationException when there is an active iterator for the array.
     * @throws InvalidSubjectNodeException when the operation described by the arguments is invalid.
     */
    public function insert(Node $node, ?Node $beforeNode): void;

    /**
     * Replace the old $nodeOrKey with the supplied node.
     *
     * @throws InvalidSubjectNodeException when $nodeOrKey is not a member of the array.
     * @throws InvalidOperationException when the arguments do not describe a valid replacement operation.
     */
    public function replace(Node|int $nodeOrKey, Node $newNode): void

    /**
     * Remove the supplied node from the array.
     *
     * @throws InvalidOperationException if the supplied node is not a member of the array.
     */
    public function remove(Node $node): void;
}
```

```php
abstract class \DaveRandom\Jom\ObjectNode extends \DaveRandom\Jom\VectorNode
{
    /**
     * Returns TRUE if the object has a property with the supplied name, otherwise FALSE.
     *
     * @throws InvalidKeyException when the property does not exist.
     */
    public function hasProperty(string $name): bool;

    /**
     * Get the value node associated with the supplied property name.
     *
     * @throws InvalidKeyException when the property does not exist.
     */
    public function getProperty(string $name): Node;

    /**
     * Set the value node associated with the supplied property name.
     *
     * @throws InvalidSubjectNodeException when the operation described by the arguments is invalid.
     */
    public function setProperty(string $name, Node $value): void;

    /**
     * Remove the value node associated with the supplied property name.
     *
     * @throws InvalidKeyException when the property does not exist.
     */
    public function removeProperty(string $name): void;
}
```


Exception Hierarchy
---

```
\Exception
  ┗ \DaveRandom\Jom\Exceptions\Exception
      ┣ \DaveRandom\Jom\Exceptions\InvalidNodeValueException
      ┣ \DaveRandom\Jom\Exceptions\InvalidOperationException
      ┃   ┣ \DaveRandom\Jom\Exceptions\InvalidKeyException
      ┃   ┗ \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
      ┣ \DaveRandom\Jom\Exceptions\InvalidPointerException
      ┣ \DaveRandom\Jom\Exceptions\ParseFailureException
      ┗ \DaveRandom\Jom\Exceptions\PointerEvaluationFailureException
```
