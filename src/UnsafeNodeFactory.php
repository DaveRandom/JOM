<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;

final class UnsafeNodeFactory extends NodeFactory
{
    /**
     * @throws InvalidNodeValueException
     */
    private function createVectorNodeFromArray(array $values, ?Document $doc, int $flags): VectorNode
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
            ? $this->createArrayNodeFromPackedArray($values, $doc, $flags)
            : $this->createObjectNodeFromPropertyMap($values, $doc, $flags);
    }

    /**
     * @throws InvalidNodeValueException
     */
    private function createNodeFromObject(object $object, ?Document $doc, int $flags): ?Node
    {
        return $object instanceof \JsonSerializable
            ? $this->createNodeFromValue($object->jsonSerialize(), $doc, $flags)
            : $this->createObjectNodeFromPropertyMap($object, $doc, $flags);
    }

    /**
     * @inheritdoc
     */
    public function createNodeFromValue($value, ?Document $doc, int $flags): ?Node
    {
        try {
            if (null !== $node = $this->createScalarOrNullNodeFromValue($value, $doc)) {
                return $node;
            }

            if (\is_object($value)) {
                return $this->createNodeFromObject($value, $doc, $flags);
            }

            if (\is_array($value)) {
                return $this->createVectorNodeFromArray($value, $doc, $flags);
            }

            if ($flags & Node::IGNORE_INVALID_VALUES) {
                return null;
            }
        } catch (InvalidNodeValueException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd

        throw new InvalidNodeValueException("Failed to create node from value of type '" . \gettype($value) . "'");
    }
}
