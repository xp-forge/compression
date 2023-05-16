<?php namespace io\streams\compress;

use io\IOException;
use io\streams\InputStream;

/**
 * InputStream that decompresses data compressed using GZIP encoding.
 *
 * @ext  zlib
 * @see  https://www.rfc-editor.org/rfc/rfc1952.html
 * @testio.streams.compress.unittest.GzipInputStreamTest
 */
class GzipInputStream implements InputStream {
  private $fd, $header;
  public static $wrapped= [];

  static function __static() {
    stream_wrapper_register('zlib.bounded', get_class(new class() {
      protected $id, $st= null;
      protected $buffer= '';
      public $context = null;
      
      public function stream_open($path, $mode, $options, $opened_path) {
        $this->st= GzipInputStream::$wrapped[$path];
        $this->id= $path;
        return true;
      }

      public function stream_read($count) {

        // Ensure we have at least 9 bytes
        $l= strlen($this->buffer);
        while ($l < 9 && $this->st->available() > 0) {
          $chunk= $this->st->read($count);
          $l+= strlen($chunk);
          $this->buffer.= $chunk;
        }
        
        // Now return the everything except the last 8 bytes
        $read= substr($this->buffer, 0, -8);
        $this->buffer= substr($this->buffer, -8);
        return $read;
      }

      public function stream_eof() {
        return 0 === $this->st->available();
      }

      public function stream_flush() {
        return true;
      }
      
      public function stream_close() {
        $this->st->close();
        unset(GzipInputStream::$wrapped[$this->id]);
      }
    }));
  }
  
  /**
   * Constructor
   *
   * @param  io.streams.InputStream $in
   * @throws io.IOException
   */
  public function __construct(InputStream $in) {
    $header= '';
    while (($l= strlen($header)) < 10 && $in->available()) {
      $header.= $in->read(10 - $l);
    }

    // Read GZIP format header
    // * ID1, ID2 (Identification, \x1F, \x8B)
    // * CM       (Compression Method, 8 = deflate)
    // * FLG      (Flags)
    // * MTIME    (Modification time, Un*x timestamp)
    // * XFL      (Extra flags)
    // * OS       (Operating system)
    $this->header= unpack('a2id/Cmethod/Cflags/Vtime/Cextra/Cos', $header);
    if ("\x1F\x8B" !== $this->header['id']) {
      $e= new IOException('Invalid format, expected \037\213, have '.addcslashes($this->header['id'], "\0..\377"));
      \xp::gc(__FILE__);
      throw $e;
    }

    if (8 !== $this->header['method']) {
      throw new IOException('Unknown compression method #'.$this->header['method']);
    }

    // Extract filename if present
    if (8 === ($this->header['flags'] & 8)) {
      $this->header['filename']= '';
      while ("\x00" !== ($b= $in->read(1))) {
        $this->header['filename'].= $b;
      }
    }

    // Now, convert stream to file handle and append inflating filter
    $uri= 'zlib.bounded://'.spl_object_hash($in);
    self::$wrapped[$uri]= $in;

    $this->fd= fopen($uri, 'r');
    if (!stream_filter_append($this->fd, 'zlib.inflate', STREAM_FILTER_READ)) {
      fclose($this->fd);
      $this->fd= null;
      throw new IOException('Could not append stream filter');
    }
  }

  /** @return [:var] */
  public function header() { return $this->header; }

  /**
   * Read a string
   *
   * @param  int $limit default 8192
   * @return string
   */
  public function read($limit= 8192) {
    if (false === ($bytes= fread($this->fd, $limit))) {
      $e= new IOException('Reading compressed data failed');
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
