<?php namespace io\streams\compress;

use io\IOException;
use io\streams\{OutputStream, Streams};
use lang\IllegalArgumentException;

/**
 * OuputStream that compresses content using bzip2
 *
 * @ext  bz2
 * @test io.streams.compress.unittest.BzipOutputStreamTest
 */
class Bzip2OutputStream implements OutputStream {
  private $fd;
  
  /**
   * Constructor
   *
   * @param  io.streams.OutputStream $out
   * @param  int $level default 6
   * @throws lang.IllegalArgumentException if the level is not between 0 and 9
   * @throws io.IOException
   */
  public function __construct(OutputStream $out, $level= 6) {
    if ($level < 0 || $level > 9) {
      throw new IllegalArgumentException('Level '.$level.' out of range [0..9]');
    }

    $this->fd= Streams::writeableFd($out);
    if (!stream_filter_append($this->fd, 'bzip2.compress', STREAM_FILTER_WRITE, ['blocks' => $level])) {
      fclose($this->fd);
      $this->fd= null;
      throw new IOException('Could not append stream filter');
    }
  }
  
  /**
   * Write a string
   *
   * @param string $arg
   * @return void
   */
  public function write($arg) {
    fwrite($this->fd, $arg);
  }

  /**
   * Flush this buffer
   *
   * @return void
   */
  public function flush() {
    fflush($this->fd);
  }

  /**
   * Close this buffer. Flushes this buffer and then calls the close()
   * method on the underlying OuputStream.
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
    return nameof($this).'(->'.$this->in.')';
  }
}
