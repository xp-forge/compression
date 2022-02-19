<?php namespace io\streams\compress;

use io\IOException;
use io\streams\{Streams, InputStream};

/**
 * InputStream that decompresses using bzip2
 *
 * @ext   bz2
 * @test  io.streams.compress.unittest.Bzip2InputStreamTest
 */
class Bzip2InputStream implements InputStream {
  private $fd;
  
  /**
   * Constructor
   *
   * @param  io.streams.InputStream $in
   * @throws io.IOException
   */
  public function __construct(InputStream $in) {
    $this->fd= Streams::readableFd($in);
    if (!stream_filter_append($this->fd, 'bzip2.decompress', STREAM_FILTER_READ)) {
      fclose($this->fd);
      $this->fd= null;
      throw new IOException('Could not append stream filter');
    }
  }

  /**
   * Read a string
   *
   * @param  int $limit default 8192
   * @return string
   */
  public function read($limit= 8192) {
    return fread($this->fd, $limit);
  }

  /**
   * Returns the number of bytes that can be read from this stream 
   * without blocking.
   *
   * @return int
   */
  public function available() {
    return feof($this->fd) ? 0 : 1;
  }

  /**
   * Close this buffer.
   *
   * @return void
   */
  public function close() {
    if (!$this->fd) return;

    fclose($this->fd);
    $this->fd= null;
  }
  
  /** Ensures output stream is closed. */
  public function __destruct() {
    $this->close();
  }

  /** @return string */
  public function toString() {
    return nameof($this).'(->'.$this->fd.')';
  }
}