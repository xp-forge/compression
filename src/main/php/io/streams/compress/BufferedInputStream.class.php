<?php namespace io\streams\compress;

use io\streams\InputStream;
use lang\IllegalArgumentException;

/** @test io.streams.compress.unittest.BufferedInputStreamTest */
class BufferedInputStream implements InputStream {
  private $in, $decompress;
  private $buffer= null, $position= 0;

  /**
   * Creates a new decompressing input stream
   *
   * @param  io.streams.InputStream $in The stream to read from
   * @param  io.streams.compress.Algorithm|function(string): string $decompress
   * @throws lang.IllegalArgumentException
   */
  public function __construct(InputStream $in, $decompress) {
    if ($decompress instanceof Algorithm) {
      $this->decompress= [$decompress, 'decompress'];
    } else if (is_callable($decompress)) {
      $this->decompress= $decompress;
    } else {
      throw new IllegalArgumentException('Expected an Algorithm or a callable, have '.typeof($decompress));
    }
    $this->in= $in;
  }

  /** @return string */
  private function buffer() {
    if (null === $this->buffer) {
      $compressed= '';
      while ($this->in->available()) {
        $compressed.= $this->in->read();
      }

      $this->buffer= ($this->decompress)($compressed);
    }
    return $this->buffer;
  }

  /**
   * Read a string
   *
   * @param   int limit default 8192
   * @return  string
   */
  public function read($limit= 8192) {
    $chunk= substr($this->buffer(), $this->position, $limit);
    $this->position+= strlen($chunk);
    return $chunk;
  }

  /**
   * Returns the number of bytes that can be read from this stream 
   * without blocking.
   *
   * @return int
   */
  public function available() {
    return strlen($this->buffer()) - $this->position;
  }

  /**
   * Close this buffer.
   *
   * @return void
   */
  public function close() {
    $this->buffer= null;
    $this->in->close();
  }

  /** Ensures input stream is closed */
  public function __destruct() {
    $this->close();
  }
}