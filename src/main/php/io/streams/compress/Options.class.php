<?php namespace io\streams\compress;

use io\streams\Compression;
use lang\Value;
use util\Comparison;

/** @test io.streams.compress.unittest.OptionsTest */
class Options implements Value {
  use Comparison;

  public $level, $length;

  /**
   * Compression options
   * 
   * @param  ?int $level
   * @param  ?int $length
   */
  public function __construct(
    $level= null,
    $length= null
  ) {
    $this->level= $level ?? Compression::DEFAULT;
    $this->length= $length;
  }

  /** @param ?int|[:var]|self $arg */
  public static function from($arg): self {
    if (null === $arg) {
      return new self();
    } else if ($arg instanceof self) {
      return $arg;
    } else if (is_array($arg)) {
      return new self(
        $arg['level'] ?? null,
        $arg['length'] ?? null
      );
    } else {
      return new self($arg);
    }
  }

  /** @return string */
  public function toString() {
    switch ($this->level) {
      case Compression::FASTEST: $level= 'FASTEST'; break;
      case Compression::DEFAULT: $level= 'DEFAULT'; break;
      case Compression::STRONGEST: $level= 'STRONGEST'; break;
      default: $level= $this->level;
    }

    return sprintf(
      '%s(level: %s, length: %s)',
      nameof($this),
      $level,
      null === $this->length ? 'null' : $this->length
    );
  }
}