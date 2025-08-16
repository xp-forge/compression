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
  private $in, $handle;

  /**
   * Creates a new compressing output stream
   *
   * @param  io.streams.InputStream $in The stream to read from
   */
  public function __construct(InputStream $in) {
    $this->in= $in;
    $this->handle= zstd_uncompress_init();
  }

  /**
   * Read a string
   *
   * @param   int limit default 8192
   * @return  string
   */
  public function read($limit= 8192) {
    $bytes= zstd_uncompress_add($this->handle, $this->in->read($limit));
    if (false === $bytes) {
      $e= new IOException('Failed to uncompress');
      \xp::gc(__FILE__);
      throw $e;
    }
    return $bytes;
  }

  /**
   * Returns the number of bytes that can be read from this stream 
   * without blocking.
   *
   * @return int
   */
  public function available() {
    return $this->in->available();
  }

  /**
   * Close this buffer.
   *
   * @return void
   */
  public function close() {
    if ($this->handle) {
      $this->handle= null;
      $this->in->close();
    }
  }
  
  /** Ensures input stream is closed */
  public function __destruct() {
    $this->close();
  }
}