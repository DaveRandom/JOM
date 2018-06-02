<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidPointerException;

final class Pointer
{
    private $path;
    private $relativeLevels;
    private $keyLookup;

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

    /**
     * @throws InvalidPointerException
     */
    public static function createFromString(string $pointer): self
    {
        $relativeLevels = null;

        if (\preg_match('/^(0|[1-9][0-9]*)([^0-9].*)$/i', $pointer, $match)) {
            $relativeLevels = (int)$match[1];
            $pointer = $match[2];

            if ($pointer === '#') {
                return new self([], $relativeLevels, true);
            }
        }

        if ($pointer === '') {
            return new self([], $relativeLevels, false);
        }

        if ($pointer[0] !== '/') {
            throw new InvalidPointerException('JSON pointer must be the empty string begin with /');
        }

        return new self(self::decodePath($pointer), $relativeLevels, false);
    }

    /**
     * @throws InvalidPointerException
     */
    public function __construct(array $path, ?int $relativeLevels = null, ?bool $isKeyLookup = false)
    {
        if ($relativeLevels < 0) {
            throw new InvalidPointerException('Relative levels must be positive');
        }

        if ($isKeyLookup && $relativeLevels === null) {
            throw new InvalidPointerException('Key lookups are only valid for relative pointers');
        }

        $this->path = $path;
        $this->relativeLevels = $relativeLevels;
        $this->keyLookup = $isKeyLookup ?? false;
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

        if ($this->isRelative()) {
            $this->string .= $this->relativeLevels;
        }

        $this->string .= $this->isKeyLookup()
            ? '#'
            : self::encodePath($this->path);

        return $this->string;
    }
}
