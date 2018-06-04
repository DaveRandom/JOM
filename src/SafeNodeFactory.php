<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class SafeNodeFactory extends NodeFactory
{
    /**
     * @inheritdoc
     */
    public function createVectorNodeFromValue($value, ?Document $doc, int $flags): ?Node
    {
        if (\is_object($value)) {
            return $this->createObjectNodeFromPropertyMap($value, $doc, $flags);
        }

        if (\is_array($value)) {
            return $this->createArrayNodeFromPackedArray($value, $doc, $flags);
        }

        return null;
    }
}
