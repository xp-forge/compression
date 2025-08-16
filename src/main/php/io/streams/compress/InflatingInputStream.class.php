<?php namespace io\streams\compress;

use io\IOException;
use io\streams\{Streams, InputStream};

/**
 * InputStream that inflates 
 *
 * @ext   zlib
 * @test  io.streams.compress.InflatingInputStreamTest
 */
class InflatingInputStream implements InputStream {
  protected $in;
  
  /**
   * Constructor
   *
   * @param   io.streams.InputStream in
   */
  public function __construct(InputStream $in) {
    $this->in= Streams::readableFd($in);
    if (!stream_filter_append($this->in, 'zlib.inflate', STREAM_FILTER_READ)) {
      throw new IOException('Could not append stream filter');
    }
  }

  /**
   * Read a string
   *
   * @param   int limit default 8192
   * @return  string
   */
  public function read($limit= 8192) {
    return fread($this->in, $limit);
  }

  /**
   * Returns the number of bytes that can be read from this stream 
   * without blocking.
   *
   */
  public function available() {
    return (int)($this->in && !feof($this->in));
  }

  /**
   * Close this buffer.
   *
   */
  public function close() {
    if (!$this->in) return;
    fclose($this->in);
    $this->in= null;
  }
  
  /** Ensures input stream is closed */
  public function __destruct() {
    $this->close();
  }

  /** @return string */
  public function toString() {
    return nameof($this).'(->'.$this->in.')';
  }
}
