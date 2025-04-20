<?php namespace io\streams\compress;

use io\IOException;
use io\streams\InputStream;

/**
 * ZStandard input stream
 *
 * @ext  zstd
 * @test io.streams.compress.unittest.BrotliInputStreamTest
 * @see  https://github.com/kjdev/php-ext-zstd
 */
class ZStandardInputStream implements InputStream {
  private $in;
  private $buffer= null, $position= 0;

  /**
   * Creates a new compressing output stream
   *
   * @param  io.streams.InputStream $in The stream to read from
   */
  public function __construct(InputStream $in) {
    $this->in= $in;
  }

  /** @see https://github.com/kjdev/php-ext-zstd/issues/64 */
  private function buffer() {
    if (null === $this->buffer) {
      $compressed= '';
      while ($this->in->available()) {
        $compressed.= $this->in->read();
      }

      $this->buffer= zstd_uncompress($compressed);
      if (false === $this->buffer) {
        $e= new IOException('Failed to uncompress');
        \xp::gc(__FILE__);
        throw $e;
      }
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
  
  /**
   * Destructor. Ensures output stream is closed.
   */
  public function __destruct() {
    $this->close();
  }
}