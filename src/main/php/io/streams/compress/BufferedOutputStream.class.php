<?php namespace io\streams\compress;

use io\streams\OutputStream;
use lang\IllegalArgumentException;

/** @test io.streams.compress.unittest.BufferedOutputStreamTest */
class BufferedOutputStream implements OutputStream {
  private $out, $compress;
  private $buffer= '';

  /**
   * Creates a new compressing output stream
   *
   * @param  io.streams.OutputStream $out The stream to write to
   * @param  io.streams.compress.Algorithm|function(string): string $compress
   * @throws lang.IllegalArgumentException
   */
  public function __construct(OutputStream $out, $compress) {
    if ($compress instanceof Algorithm) {
      $this->compress= [$compress, 'compress'];
    } else if (is_callable($compress)) {
      $this->compress= $compress;
    } else {
      throw new IllegalArgumentException('Expected an Algorithm or a callable, have '.typeof($compress));
    }
    $this->out= $out;
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
      $this->out->write(($this->compress)($this->buffer));
      $this->buffer= null;
    }
  }
}