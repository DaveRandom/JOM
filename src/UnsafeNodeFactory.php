<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\Exception;

final class UnsafeNodeFactory extends NodeFactory
{
    /**
     * @throws Exception
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
     * @throws Exception
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
    protected function createVectorNodeFromValue($value, ?Document $doc, int $flags): ?Node
    {
        if (\is_object($value)) {
            return $this->createNodeFromObject($value, $doc, $flags);
        }

        if (\is_array($value)) {
            return $this->createVectorNodeFromArray($value, $doc, $flags);
        }

        return null;
    }
}
