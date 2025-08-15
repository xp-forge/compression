<?php namespace io\streams\compress;

use io\streams\OutputStream;

/** @test io.streams.compress.unittest.SnappyOutputStreamTest */
class SnappyOutputStream implements OutputStream {
  private $out;
  private $buffer= '';

  /**
   * Creates a new compressing output stream
   *
   * @param  io.streams.OutputStream $out The stream to write to
   * @param  int $length uncompressed length
   */
  public function __construct(OutputStream $out, $length) {
    $this->out= $out;
    $this->out->write(Snappy::length($length));
  }

  /** Compare 4-byte offsets in data at offsets a and b */
  private function equals32(int $a, int $b): bool {
    return (
      $this->buffer[$a] === $this->buffer[$b] &&
      $this->buffer[$a + 1] === $this->buffer[$b + 1] &&
      $this->buffer[$a + 2] === $this->buffer[$b + 2] &&
      $this->buffer[$a + 3] === $this->buffer[$b + 3]
    );
  }

  /** Compresses a fragment and returns last emitted position */
  private function fragment() {
    $end= min(strlen($this->buffer), Snappy::BLOCK_SIZE);
    $pos= $emit= 0;
    $out= '';

    if ($end >= Snappy::INPUT_MARGIN) {
      $bits= 1;
      while ((1 << $bits) <= $end && $bits <= Snappy::HASH_BITS) {
        $bits++;
      }
      $bits--;
      $shift= 32 - $bits;
      $hashtable= array_fill(0, 1 << $bits, 0);

      $limit= $end - Snappy::INPUT_MARGIN;
      $next= ((unpack('V', $this->buffer, ++$pos)[1] * Snappy::HASH_KEY) & 0xffffffff) >> $shift;

      // Emit literals
      next: $forward= $pos;
      $skip= 32;
      do {
        $pos= $forward;
        $hash= $next;
        $forward+= ($skip & 0xffffffff) >> 5;
        $skip++;
        if ($pos > $limit || $forward > $limit) goto emit;

        $next= ((unpack('V', $this->buffer, $forward)[1] * Snappy::HASH_KEY) & 0xffffffff) >> $shift;
        $candidate= $hashtable[$hash];
        $hashtable[$hash]= $pos & 0xffff;
      } while (!$this->equals32($pos, $candidate));

      $out.= Snappy::literal($pos - $emit).substr($this->buffer, $emit, $pos - $emit);

      // Emit copy instructions
      do {
        $offset= $pos - $candidate;
        $matched= 4;
        while ($pos + $matched < $end && $this->buffer[$pos + $matched] === $this->buffer[$candidate + $matched]) {
          $matched++;
        }
        $pos+= $matched;

        while ($matched >= 68) {
          $out.= Snappy::copy($offset, 64);
          $matched-= 64;
        }
        if ($matched > 64) {
          $out.= Snappy::copy($offset, 60);
          $matched-= 60;
        }
        $out.= Snappy::copy($offset, $matched);
        $emit= $pos;

        if ($pos >= $limit) goto emit;

        $hash= ((unpack('V', $this->buffer, $pos - 1)[1] * Snappy::HASH_KEY) & 0xffffffff) >> $shift;
        $hashtable[$hash]= ($pos - 1) & 0xffff;
        $hash= ((unpack('V', $this->buffer, $pos)[1] * Snappy::HASH_KEY) & 0xffffffff) >> $shift;
        $candidate= $hashtable[$hash];
        $hashtable[$hash]= $pos & 0xffff;
      } while ($this->equals32($pos, $candidate));

      $pos++;
      $next= ((unpack('V', $this->buffer, $pos)[1] * Snappy::HASH_KEY) & 0xffffffff) >> $shift;
      goto next;
    }

    emit: if ($emit < $end) {
      $out.= Snappy::literal($end - $emit).substr($this->buffer, $emit, $end - $emit);
    }

    $this->buffer= substr($this->buffer, $end);
    return $out;
  }

  /**
   * Write a string
   *
   * @param  var $arg
   * @return void
   */
  public function write($arg) {
    $this->buffer.= $arg;
    if (strlen($this->buffer) > Snappy::BLOCK_SIZE) {
      $this->out->write($this->fragment());
    }
  }

  /**
   * Flush this buffer
   *
   * @return void
   */
  public function flush() {
    if (strlen($this->buffer) > 0) {
      $this->out->write($this->fragment());
    }
  }

  /**
   * Closes this object. May be called more than once, which may
   * not fail - that is, if the object is already closed, this 
   * method should have no effect.
   *
   * @return void
   */
  public function close() {
    if (strlen($this->buffer) > 0) {
      $this->out->write($this->fragment());
      $this->buffer= '';
    }
    $this->out->close();
  }

  /** Ensures output stream is closed */
  public function __destruct() {
    $this->close();
  }
}