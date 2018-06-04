<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidPointerException;

final class Pointer
{
    private $path = [];
    private $relativeLevels = null;
    private $keyLookup = false;

    private $string;

    private static function decodePath(string $path): array
    {
        $result = [];

        foreach (\explode('/', \substr($path, 1)) as $component) {
            $result[] = \str_replace(['~1', '~0'], ['/', '~'], $component);
        }

        return $result;
    }

    private static function encodePath(array $path): string
    {
        $result = '';

        foreach ($path as $component) {
            $result .= '/' . \str_replace(['~', '/'], ['~0', '~1'], $component);
        }

        return $result;
    }

    private function __construct() { }

    /**
     * @throws InvalidPointerException
     */
    public static function createFromString(string $pointer): self
    {
        $result = new self();

        if (\preg_match('/^(0|[1-9][0-9]*)($|[^0-9].*)/i', $pointer, $match)) {
            $result->relativeLevels = (int)$match[1];
            $pointer = $match[2];

            if ($pointer === '#') {
                $result->keyLookup = true;
                return $result;
            }
        }

        if ($pointer === '') {
            return $result;
        }

        if ($pointer[0] !== '/') {
            throw new InvalidPointerException('JSON pointer must be the empty string or begin with /');
        }

        $result->path = self::decodePath($pointer);

        return $result;
    }

    /**
     * @param string[] $path
     * @throws InvalidPointerException
     */
    public static function createFromParameters(array $path, ?int $relativeLevels = null, ?bool $isKeyLookup = false): self
    {
        if ($relativeLevels < 0) {
            throw new InvalidPointerException('Relative levels must be positive');
        }

        if ($isKeyLookup && $relativeLevels === null) {
            throw new InvalidPointerException('Key lookups are only valid for relative pointers');
        }

        $result = new self();

        foreach ($path as $component) {
            $result->path[] = (string)$component;
        }

        $result->relativeLevels = $relativeLevels;
        $result->keyLookup = $isKeyLookup ?? false;

        return $result;
    }

    public function getPath(): array
    {
        return $this->path;
    }

    public function getRelativeLevels(): ?int
    {
        return $this->relativeLevels;
    }

    public function isRelative(): bool
    {
        return $this->relativeLevels !== null;
    }

    public function isKeyLookup(): bool
    {
        return $this->keyLookup;
    }

    public function __toString(): string
    {
        if (isset($this->string)) {
            return $this->string;
        }

        $this->string = '';

        if ($this->relativeLevels !== null) {
            $this->string .= $this->relativeLevels;
        }

        $this->string .= $this->keyLookup
            ? '#'
            : self::encodePath($this->path);

        return $this->string;
    }
}
