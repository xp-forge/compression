<?php namespace io\streams\compress;

use io\streams\OutputStream;
use lang\IllegalArgumentException;

/**
 * ZStandard output stream
 *
 * @ext  zstd
 * @test io.streams.compress.unittest.ZStandardOutputStreamTest
 * @see  https://github.com/kjdev/php-ext-zstd
 */
class ZStandardOutputStream implements OutputStream {
  private $out, $level;
  private $buffer= '';

  /**
   * Creates a new compressing output stream
   *
   * @param  io.streams.OutputStream $out The stream to write to
   * @param  int $level
   * @throws lang.IllegalArgumentException
   */
  public function __construct(OutputStream $out, $level= ZSTD_COMPRESS_LEVEL_DEFAULT) {
    if ($level < ZSTD_COMPRESS_LEVEL_MIN || $level > ZSTD_COMPRESS_LEVEL_MAX) {
      throw new IllegalArgumentException('Level must be between '.ZSTD_COMPRESS_LEVEL_MIN.' and '.ZSTD_COMPRESS_LEVEL_MAX);
    }

    $this->out= $out;
    $this->level= $level;
  }

  /**
   * Write a string
   *
   * @param  var $arg
   * @return void
   */
  public function write($arg) {
    $this->buffer.= $arg;
  }

  /**
   * Flush this buffer
   *
   * @return void
   */
  public function flush() {
    // NOOP
  }

  /**
   * Closes this object. May be called more than once, which may
   * not fail - that is, if the object is already closed, this 
   * method should have no effect.
   *
   * @return void
   */
  public function close() {
    if (null !== $this->buffer) {
      $this->out->write(zstd_compress($this->buffer, $this->level));
      $this->buffer= null;
    }
  }
}