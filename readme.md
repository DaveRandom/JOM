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
     * Create an empty document.
     */
    public __construct();

    /**
     * Create a Document instance from a JSON string, forwarding arguments to json_encode().
     *
     * @throws ParseFailureException when the supplied string cannot be parsed as JSON.
     * @throws DocumentTreeCreationFailedException when a document tree cannot be built from the parsed data.
     */
    public static Document parse(string $json, int $depthLimit = 512, int $options = 0);

    /**
     * Create a Document instance from a PHP value.
     *
     * @throws DocumentTreeCreationFailedException when a document tree cannot be built from the supplied data.
     */
    public static Document createFromData($data);

    /**
     * Returns the root node of the document, or NULL if the document is empty.
     */
    public ?Node getRootNode();

    /**
     * Evaluate a JSON pointer against the document tree.
     *
     * @throws InvalidPointerException when the supplied pointer string is invalid
     * @throws PointerEvaluationFailureException when the pointer does not indiciate a valid location in the document
     * @throws InvalidSubjectNodeException when the $base node is not part of the document
     */
    public Node|int|string evaluatePointer(Pointer|string $pointer, Node $base = null);
}
```

```php
abstract class \DaveRandom\Jom\Node
    implements \JsonSerializable
{
    /**
     * Default Node constructor
     */
    public __construct(?Document $ownerDocument = null);

    /**
     * Returns the parent node of this node, or NULL if this is the root node or the node is not present in the owning
     * document.
     */
    public ?Node getParent();

    /**
     * Returns the next sibling node of this node, or NULL if the node does not have a following sibling node.
     */
    public ?Node getPreviousSibling();

    /**
     * Returns the previous sibling node of this node, or NULL if the node does not have a preceding sibling node.
     */
    public ?Node getNextSibling();

    /**
     * Returns TRUE if this node has child nodes, otherwise FALSE.
     */
    public bool hasChildren();

    /**
     * Returns the first child node of this node, or NULL if the node does not have any child nodes.
     */
    public ?Node getFirstChild();

    /**
     * Returns the last child node of this node, or NULL if the node does not have any child nodes.
     */
    public ?Node getLastChild();

    /**
     * Returns the Document object that owns this node.
     */
    public ?Document getOwnerDocument();

    /**
     * Get a JSON pointer for this node's position in the document. If the $base node is supplied, get a relative
     * pointer.
     *
     * @throws InvalidSubjectNodeException when the $base node is invalid
     */
    public Pointer getPointer(Node $base = null);

    /**
     * Returns the key of this node within its parent node, or NULL if this is the root node of the document.
     */
    public string|int|null getKey();

    /**
     * Returns the data represented by this node as the appropriate PHP type.
     */
    public mixed getValue();
}
```

```php
/**
 * Represents a NULL value node.
 */
final class \DaveRandom\Jom\NullNode extends \DaveRandom\Jom\Node { }
```

```php
final class \DaveRandom\Jom\BooleanNode extends \DaveRandom\Jom\Node
{
    /**
     * Create a new boolean value node owned by the supplied document.
     */
    public __construct(?Document $ownerDocument = null, bool $value = false);

    /**
     * Set the value of this node
     */
    public void setValue(bool $value);
}
```

```php
final class \DaveRandom\Jom\NumberNode extends \DaveRandom\Jom\Node
{
    /**
     * Create a new number value node owned by the supplied document.
     */
    public __construct(?Document $ownerDocument = null, int|float $value = 0);

    /**
     * Set the value of this node
     */
    public void setValue(int|float $value);
}
```

```php
final class \DaveRandom\Jom\StringNode extends \DaveRandom\Jom\Node
{
    /**
     * Create a new string value node owned by the supplied document.
     */
    public __construct(?Document $ownerDocument = null, string $value = "");

    /**
     * Set the value of this node
     */
    public void setValue(string $value);
}
```

```php
abstract class \DaveRandom\Jom\VectorNode extends \DaveRandom\Jom\Node 
    implements \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * Returns the data represented by this node as an array.
     */
    public array toArray();
}
```

```php
abstract class \DaveRandom\Jom\ArrayNode extends \DaveRandom\Jom\VectorNode
{
    /**
     * Append a node to the array.
     *
     * @throws InvalidSubjectNodeException when $node is invalid.
     * @throws WriteOperationForbiddenException when there is an active iterator for the array.
     */
    public void push(Node $node);

    /**
     * Remove the last node from the array, if any, and return it.
     *
     * @throws WriteOperationForbiddenException when there is an active iterator for the array.
     */
    public ?Node pop();

    /**
     * Prepend a node to the array.
     *
     * @throws InvalidSubjectNodeException when $node is invalid.
     * @throws WriteOperationForbiddenException when there is an active iterator for the array.
     */
    public void unshift(Node $node);

    /**
     * Remove the first node from the array, if any, and return it.
     *
     * @throws WriteOperationForbiddenException when there is an active iterator for the array.
     */
    public ?Node shift();

    /**
     * Insert a new node before the supplied reference node. If the reference node is NULL it is equivalent to push().
     *
     * @throws InvalidSubjectNodeException when $beforeNode is not a member of the array, or $node is invalid.
     * @throws WriteOperationForbiddenException when there is an active iterator for the array.
     */
    public void insert(Node $node, ?Node $beforeNode);

    /**
     * Replace the old $nodeOrKey with the supplied node.
     *
     * @throws InvalidSubjectNodeException when $nodeOrKey is not a member of the array, or $newNode is invalid.
     * @throws WriteOperationForbiddenException when there is an active iterator for the array.
     */
    public void replace(Node|int $nodeOrKey, Node $newNode);

    /**
     * Remove the supplied node from the array.
     *
     * @throws WriteOperationForbiddenException when there is an active iterator for the array.
     * @throws InvalidSubjectNodeException when $node is not a member of the array.
     */
    public void remove(Node $node);
}
```

```php
abstract class \DaveRandom\Jom\ObjectNode extends \DaveRandom\Jom\VectorNode
{
    /**
     * Returns TRUE if the object has a property with the supplied name, otherwise FALSE.
     */
    public bool hasProperty(string $name);

    /**
     * Get the value node associated with the supplied property name.
     *
     * @throws InvalidKeyException when the property does not exist.
     */
    public Node getProperty(string $name);

    /**
     * Set the value node associated with the supplied property name.
     *
     * @throws InvalidSubjectNodeException when $value is invalid.
     * @throws WriteOperationForbiddenException when there is an active iterator for the array.
     */
    public void setProperty(string $name, Node $value);

    /**
     * Remove the supplied property.
     *
     * @throws InvalidSubjectNodeException when $nodeOrName is a Node that is not a property of the object.
     * @throws InvalidKeyException when $nodeOrName is the name of a property that does not exist.
     * @throws WriteOperationForbiddenException when there is an active iterator for the array.
     */
    public void removeProperty(Node|string $nodeOrName);
}
```


Exception Hierarchy
---

```
\Exception
  ┗ \DaveRandom\Jom\Exceptions\Exception
      ┣ \DaveRandom\Jom\Exceptions\DocumentTreeCreationFailedException
      ┣ \DaveRandom\Jom\Exceptions\InvalidNodeValueException
      ┣ \DaveRandom\Jom\Exceptions\InvalidOperationException
      ┃   ┣ \DaveRandom\Jom\Exceptions\InvalidKeyException
      ┃   ┣ \DaveRandom\Jom\Exceptions\InvalidSubjectNodeException
      ┃   ┗ \DaveRandom\Jom\Exceptions\WriteOperationForbiddenException
      ┣ \DaveRandom\Jom\Exceptions\InvalidPointerException
      ┣ \DaveRandom\Jom\Exceptions\ParseFailureException
      ┗ \DaveRandom\Jom\Exceptions\PointerEvaluationFailureException
```
