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

  /** Encode literal operation */
  private function literal(int $l): string {
    if ($l <= 60) {
      return chr(($l - 1) << 2);
    } else if ($l < 256) {
      return pack('CC', 60 << 2, $l - 1);
    } else {
      return pack('CCC', 61 << 2, ($l - 1) & 0xff, (($l - 1) & 0xffffffff) >> 8);
    }
  }

  /** Encode copy operation */
  private function copy(int $i, int $l): string {
    if ($l < 12 && $i < 2048) {
      return pack('CC', 1 + (($l - 4) << 2) + ((($i & 0xffffffff) >> 8) << 5), $i & 0xff);
    } else {
      return pack('CCC', 2 + (($l - 1) << 2), $i & 0xff, ($i & 0xffffffff) >> 8);
    }
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
    if ($end <= Snappy::INPUT_MARGIN) return 0;

    $pos= $emit= 0;
    $bits= 1;
    while ((1 << $bits) <= $end && $bits <= Snappy::HASH_BITS) {
      $bits++;
    }
    $bits--;
    $shift= 32 - $bits;
    $hashtable= array_fill(0, 1 << $bits, 0);

    $start= $pos;
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
      if ($pos > $limit) return $emit;

      $next= ((unpack('V', $this->buffer, $forward)[1] * Snappy::HASH_KEY) & 0xffffffff) >> $shift;
      $candidate= $start + $hashtable[$hash];
      $hashtable[$hash]= ($pos - $start) & 0xffff;
    } while (!$this->equals32($pos, $candidate));

    $this->out->write($this->literal($pos - $emit).substr($this->buffer, $emit, $pos - $emit));

    // Emit copy instructions
    do {
      $offset= $pos - $candidate;
      $matched= 4;
      while ($pos + $matched < $end && $this->buffer[$pos + $matched] === $this->buffer[$candidate + $matched]) {
        $matched++;
      }
      $pos+= $matched;

      while ($matched >= 68) {
        $this->out->write($this->copy($offset, 64));
        $matched-= 64;
      }
      if ($matched > 64) {
        $this->out->write($this->copy($offset, 60));
        $matched-= 60;
      }
      $this->out->write($this->copy($offset, $matched));
      $emit= $pos;

      if ($pos >= $limit) return $emit;

      $hash= ((unpack('V', $this->buffer, $pos - 1)[1] * Snappy::HASH_KEY) & 0xffffffff) >> $shift;
      $hashtable[$hash]= ($pos - 1 - $start) & 0xffff;
      $hash= ((unpack('V', $this->buffer, $pos)[1] * Snappy::HASH_KEY) & 0xffffffff) >> $shift;
      $candidate= $start + $hashtable[$hash];
      $hashtable[$hash]= ($pos - $start) & 0xffff;
    } while ($this->equals32($pos, $candidate));

    $pos++;
    $next= ((unpack('V', $this->buffer, $pos)[1] * Snappy::HASH_KEY) & 0xffffffff) >> $shift;
    goto next;
  }

  /**
   * Write a string
   *
   * @param  var $arg
   * @return void
   */
  public function write($arg) {
    if (strlen($this->buffer) <= Snappy::BLOCK_SIZE) {
      $this->buffer.= $arg;
    } else {
      $this->buffer= substr($this->buffer, $this->fragment());
    }
  }

  /**
   * Flush this buffer (except if it's smaller than the input margin)
   *
   * @return void
   */
  public function flush() {
    $this->buffer= substr($this->buffer, $this->fragment());
  }

  /**
   * Closes this object. May be called more than once, which may
   * not fail - that is, if the object is already closed, this 
   * method should have no effect.
   *
   * @return void
   */
  public function close() {
    $end= strlen($this->buffer);
    if ($end > 0) {
      $emit= $this->fragment();
      if ($emit < $end) {
        $this->out->write($this->literal($end - $emit).substr($this->buffer, $emit, $end - $emit));
      }
      $this->buffer= '';
    }

    $this->out->close();
  }
}