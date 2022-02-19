<?php namespace io\streams\compress;

use io\IOException;
use io\streams\{Streams, OutputStream};
use lang\IllegalArgumentException;

/**
 * OuputStream that compresses content using GZIP encoding. Data
 * produced with this stream can be read with the "gunzip" command
 * line utility and its "zcat", "zgrep", ... list of friends.
 *
 * @ext  zlib
 * @see  https://www.rfc-editor.org/rfc/rfc1952.html
 * @test io.streams.compress.unittest.GzipOutputStreamTest
 */
class GzipOutputStream implements OutputStream {
  private $fd, $md, $length, $filter;
  
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

    // Write GZIP format header:
    // * ID1, ID2 (Identification, \x1F, \x8B)
    // * CM       (Compression Method, 8 = deflate)
    // * FLG      (Flags, use 0)
    // * MTIME    (Modification time, Un*x timestamp)
    // * XFL      (Extra flags, 2 = compressor used maximum compression)
    // * OS       (Operating system, 255 = unknown)
    $out->write(pack('CCCCVCC', 0x1F, 0x8B, 8, 0, time(), 2, 255));
    
    // Now, convert stream to file handle and append deflating filter
    $this->fd= Streams::writeableFd($out);
    if (!($this->filter= stream_filter_append($this->fd, 'zlib.deflate', STREAM_FILTER_WRITE, $level))) {
      fclose($this->fd);
      $this->fd= null;
      throw new IOException('Could not append stream filter');
    }
    $this->md= hash_init('crc32b');
  }
  
  /**
   * Write a string
   *
   * @param string $arg
   * @return void
   */
  public function write($arg) {
    fwrite($this->fd, $arg);
    $this->length+= strlen($arg);
    hash_update($this->md, $arg);
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
  
    // Remove deflating filter so we can continue writing "raw"
    stream_filter_remove($this->filter);

    $final= hash_final($this->md, true);
    
    // Write GZIP footer:
    // * CRC32    (CRC-32 checksum)
    // * ISIZE    (Input size)
    fwrite($this->fd, pack('aaaaV', $final[3], $final[2], $final[1], $final[0], $this->length));
    fclose($this->fd);
    $this->fd= null;
    $this->md= null;
  }
  
  /** Ensures output stream is closed. */
  public function __destruct() {
    if (!$this->fd) return;

    fclose($this->fd);
    $this->fd= null;
  }

  /** @return string */
  public function toString() {
    return nameof($this).'(->'.$this->fd.')';
  }
}
