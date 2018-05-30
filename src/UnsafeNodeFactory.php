<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;

final class UnsafeNodeFactory extends NodeFactory
{
    /**
     * @throws InvalidNodeValueException
     */
    private function createVectorNodeFromArray(array $values, ?Document $doc): VectorNode
    {
        $i = 0;
        $packed = true;

        foreach ($values as $key => $value) {
            if ($key !== $i++) {
                $packed = false;
                break;
            }
        }

        return $packed
            ? $this->createArrayNodeFromPackedArray($values, $doc)
            : $this->createObjectNodeFromPropertyMap($values, $doc);
    }

    /**
     * @throws InvalidNodeValueException
     */
    private function createNodeFromObject(object $object, ?Document $doc): Node
    {
        return $object instanceof \JsonSerializable
            ? $this->createNodeFromValue($object->jsonSerialize(), $doc)
            : $this->createObjectNodeFromPropertyMap($object, $doc);
    }

    /**
     * @inheritdoc
     */
    public function createNodeFromValue($value, ?Document $doc = null): Node
    {
        try {
            if (null !== $node = $this->createScalarOrNullNodeFromValue($value, $doc)) {
                return $node;
            }

            if (\is_object($value)) {
                return $this->createNodeFromObject($value, $doc);
            }

            if (\is_array($value)) {
                return $this->createVectorNodeFromArray($value, $doc);
            }
        } catch (InvalidNodeValueException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
        //@codeCoverageIgnoreEnd

        throw new InvalidNodeValueException("Failed to create node from value of type '" . \gettype($value) . "'");
    }
}
