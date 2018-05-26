<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class NodeListIterator implements \Iterator
{
    public const INACTIVE = 0;
    public const ACTIVE = 1;

    private $firstNode;
    private $activityStateChangeNotifier;
    private $activityState = self::INACTIVE;

    /** @var Node */
    private $currentNode;

    public function __construct(Node $firstNode, callable $activityStateChangeNotifier = null)
    {
        $this->firstNode = $firstNode;
        $this->activityStateChangeNotifier = $activityStateChangeNotifier;
    }

    private function notifyActivityStateChange(int $newState): void
    {
        if ($this->activityState === $newState) {
            return;
        }

        $this->activityState = $newState;

        if ($this->activityStateChangeNotifier !== null) {
            ($this->activityStateChangeNotifier)($this->activityState);
        }
    }

    public function __destruct()
    {
        $this->notifyActivityStateChange(self::INACTIVE);
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
        $this->notifyActivityStateChange(self::INACTIVE);

        return $this->currentNode !== null;
    }

    public function rewind(): void
    {
        $this->notifyActivityStateChange(self::ACTIVE);

        $this->currentNode = $this->firstNode;
    }
}
