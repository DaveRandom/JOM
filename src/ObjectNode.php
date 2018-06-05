<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidKeyException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;
use DaveRandom\Jom\Exceptions\WriteOperationForbiddenException;

final class ObjectNode extends VectorNode
{
    /**
     * @throws InvalidSubjectNodeException
     */
    public function __construct(?array $properties = [], ?Document $ownerDocument = null)
    {
        parent::__construct($ownerDocument);

        try {
            foreach ($properties ?? [] as $name => $node) {
                $this->setProperty((string)$name, $node);
            }
        } catch (InvalidSubjectNodeException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @noinspection PhpInternalEntityUsedInspection */
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd
    }

    /**
     * @return string[]
     */
    public function getPropertyNames(): array
    {
        return \array_keys($this->children);
    }

    public function hasProperty(string $name): bool
    {
        return isset($this->children[$name]);
    }

    /**
     * @throws InvalidKeyException
     */
    public function getProperty(string $name): Node
    {
        if (!isset($this->children[$name])) {
            throw new InvalidKeyException("Property '{$name}' does not exist on the object");
        }

        return $this->children[$name];
    }

    /**
     * @throws InvalidSubjectNodeException
     * @throws WriteOperationForbiddenException
     */
    public function setProperty(string $name, Node $value): void
    {
        if (!isset($this->children[$name])) {
            $this->appendNode($value, $name);
            return;
        }

        try {
            $this->replaceNode($value, $this->children[$name]);
        } catch (WriteOperationForbiddenException | InvalidSubjectNodeException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @noinspection PhpInternalEntityUsedInspection */
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd
    }

    /**
     * @param Node|string $nodeOrName
     * @throws InvalidSubjectNodeException
     * @throws WriteOperationForbiddenException
     * @throws InvalidKeyException
     */
    public function removeProperty($nodeOrName): void
    {
        $this->removeNode($this->resolveNode($nodeOrName));
    }

    /**
     * @throws InvalidKeyException
     */
    public function offsetGet($propertyName): Node
    {
        return $this->getProperty((string)$propertyName);
    }

    /**
     * @throws InvalidSubjectNodeException
     * @throws WriteOperationForbiddenException
     */
    public function offsetSet($propertyName, $value): void
    {
        if (!($value instanceof Node)) {
            throw new \TypeError('Child must be instance of ' . Node::class);
        }

        $this->setProperty((string)$propertyName, $value);
    }

    public function getValue(): \stdClass
    {
        $result = new \stdClass;

        foreach ($this as $name => $value) {
            $result->{$name} = $value->getValue();
        }

        return $result;
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this as $name => $value) {
            $result[$name] = $value instanceof VectorNode
                ? $value->toArray()
                : $value->getValue();
        }

        return $result;
    }
}
