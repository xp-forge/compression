<?php namespace io\streams\compress;

use io\IOException;
use io\streams\InputStream;

/** @test io.streams.compress.unittest.SnappyInputStreamTest */
class SnappyInputStream implements InputStream {
  private $in, $out;
  private $limit= 0;
  private $buffer= '';

  /**
   * Returns a given amount of bytes from the buffer
   *
   * @param  int $n
   * @return string
   * @throws io.IOException
   */
  private function bytes($n) {
    while (strlen($this->buffer) < $n) {
      if ($this->in->available()) {
        $this->buffer.= $this->in->read();
      } else {
        throw new IOException('Not enough input, expected '.$n);
      }
    }

    $chunk= substr($this->buffer, 0, $n);
    $this->buffer= substr($this->buffer, $n);
    return $chunk;
  }

  /**
   * Creates a new decompressing input stream
   *
   * @param  io.streams.InputStream $in The stream to read from
   */
  public function __construct(InputStream $in) {
    $this->in= $in;
    $this->out= '';
    for ($shift= 0, $c= 255; $shift < 32, $c >= 128; $shift+= 7) {
      $c= ord($this->bytes(1));
      $this->limit|= ($c & 0x7f) << $shift;
    }
  }

  /**
   * Read a string
   *
   * @param   int limit default 8192
   * @return  string
   */
  public function read($limit= 8192) {
    $pos= $start= strlen($this->out);
    $limit= min($limit + $start, $this->limit);

    while ($pos < $limit) {
      $c= ord($this->bytes(1));
      switch ($c & 0x03) {
        case 0:
          $l= $c >> 2;
          if ($l >= 60) {
            $l= unpack('P', str_pad($this->bytes($l - 59), 8, "\0"))[1];
          }
          $this->out.= $this->bytes(++$l);
          break;

        case 1:
          $l= 4 + (($c >> 2) & 0x7);
          $offset= ord($this->bytes(1)) + (($c >> 5) << 8);
          for ($i= 0, $end= strlen($this->out) - $offset; $i < $l; $i++) {
            $this->out.= $this->out[$end + $i];
          }
          break;

        case 2:
          $l= 1 + ($c >> 2);
          $offset= unpack('v', $this->bytes(2))[1];
          for ($i= 0, $end= strlen($this->out) - $offset; $i < $l; $i++) {
            $this->out.= $this->out[$end + $i];
          }
          break;

        case 3:
          $l= 1 + ($c >> 2);
          $offset= unpack('V', $this->bytes(4))[1];
          for ($i= 0, $end= strlen($this->out) - $offset; $i < $l; $i++) {
            $this->out.= $this->out[$end + $i];
          }
          break;
      }
      $pos+= $l;
    }

    $chunk= substr($this->out, $start);

    // Once block size is reached, offets never reference anything before,
    // free memory by removing one block from the front of the output.
    while (strlen($this->out) > Snappy::BLOCK_SIZE) {
      $this->out= substr($this->out, Snappy::BLOCK_SIZE);
      $this->limit-= Snappy::BLOCK_SIZE;
    }

    return $chunk;
  }

  /**
   * Returns the number of bytes that can be read from this stream 
   * without blocking.
   *
   * @return int
   */
  public function available() {
    return $this->limit - strlen($this->out);
  }

  /**
   * Close this buffer.
   *
   * @return void
   */
  public function close() {
    $this->in->close();
  }
  
  /** Ensures input stream is closed */
  public function __destruct() {
    $this->close();
  }
}