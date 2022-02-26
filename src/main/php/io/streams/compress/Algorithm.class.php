<?php namespace io\streams\compress;

use io\streams\{Compression, InputStream, OutputStream};
use lang\Value;

abstract class Algorithm implements Value {

  /** Returns whether this algorithm is supported in the current setup */
  public abstract function supported(): bool;

  /** Returns the algorithm's name */
  public abstract function name(): string;

  /** Returns the algorithm's HTTP Content-Encoding token */
  public abstract function token(): string;

  /** Returns the algorithm's common file extension, including a leading "." */
  public abstract function extension(): string;

  /** Maps fastest, default and strongest levels */
  public abstract function level(int $select): int;

  /** Opens an input stream for reading */
  public abstract function open(InputStream $in): InputStream;

  /** Opens an output stream for writing */
  public abstract function create(OutputStream $out, int $level= Compression::DEFAULT): OutputStream;

  /** @return string */
  public function hashCode() { return crc32($this->name); }

  /** @return string */
  public function toString() {
    return sprintf(
      '%s(token: %s, extension: %s, supported: %s, levels: %d..%d)',
      nameof($this),
      $this->token(),
      $this->extension() ?: '(none)',
      $this->supported() ? 'true' : 'false',
      $this->level(Compression::FASTEST),
      $this->level(Compression::STRONGEST)
    );
  }

  /**
   * Compare this algorithm to a given value.
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? $this->name() <=> $value->name() : 1;
  }
}