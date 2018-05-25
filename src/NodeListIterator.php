<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class NodeListIterator implements \Iterator
{
    private $firstNode;
    private $activityNotifier;

    /** @var Node */
    private $currentNode;

    public function __construct(Node $firstNode, callable $activityNotifier = null)
    {
        $this->firstNode = $firstNode;
        $this->activityNotifier = $activityNotifier;
    }

    public function current(): ?Node
    {
        return $this->currentNode;
    }

    public function next(): void
    {
        $this->currentNode = $this->currentNode->getNextSibling();
    }

    public function key()
    {
        return $this->currentNode->getKey();
    }

    public function valid(): bool
    {
        if ($this->currentNode === null && $this->activityNotifier !== null) {
            ($this->activityNotifier)(false);
        }

        return $this->currentNode !== null;
    }

    public function rewind(): void
    {
        if ($this->activityNotifier !== null) {
            ($this->activityNotifier)(true);
        }

        $this->currentNode = $this->firstNode;
    }
}
