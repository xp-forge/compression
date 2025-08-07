<?php namespace io\streams\compress;

use io\IOException;
use io\streams\{InputStream, OutputStream, Compression};

/** @see https://en.wikipedia.org/wiki/Snappy_(compression) */
class Snappy extends Algorithm {
  const BLOCK_SIZE   = 65536;
  const HASH_KEY     = 0x1e35a7bd;
  const HASH_BITS    = 14;
  const INPUT_MARGIN = 15;

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return true; }

  /** Returns the algorithm's name */
  public function name(): string { return 'snappy'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'snappy'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return '.sn'; }

  /** Maps fastest, default and strongest levels */
  public function level(int $select): int { return 0; }

  /** Encode length as varint */
  public static function length(int $length): string {
    $out= '';

    shift: $l= $length & 0x7f;
    $length= ($length & 0xffffffff) >> 7;
    if ($length > 0) {
      $out.= chr($l + 0x80);
      goto shift;
    }

    return $out.chr($l);
  }

  /** Encode literal operation */
  public static function literal(int $l): string {
    if ($l <= 60) {
      return chr(($l - 1) << 2);
    } else if ($l < 256) {
      return pack('CC', 60 << 2, $l - 1);
    } else {
      return pack('CCC', 61 << 2, ($l - 1) & 0xff, (($l - 1) & 0xffffffff) >> 8);
    }
  }

  /** Encode copy operation */
  public static function copy(int $i, int $l): string {
    if ($l < 12 && $i < 2048) {
      return pack('CC', 1 + (($l - 4) << 2) + ((($i & 0xffffffff) >> 8) << 5), $i & 0xff);
    } else {
      return pack('CCC', 2 + (($l - 1) << 2), $i & 0xff, ($i & 0xffffffff) >> 8);
    }
  }

  /** Compresses data */
  public function compress(string $data, $options= null): string {
    $length= strlen($data);
    $out= self::length($length);

    // Inlined comparison of 4-byte offsets in data at offsets a and b
    $equals32= fn($a, $b) => (
      $data[$a] === $data[$b] &&
      $data[$a + 1] === $data[$b + 1] &&
      $data[$a + 2] === $data[$b + 2] &&
      $data[$a + 3] === $data[$b + 3]
    );

    for ($pos= 0; $pos < $length; $pos= $end) {
      $fragment= min($length - $pos, self::BLOCK_SIZE);
      $end= $pos + $fragment;
      $emit= $pos;

      if ($fragment >= self::INPUT_MARGIN) {
        $bits= 1;
        while ((1 << $bits) <= $fragment && $bits <= self::HASH_BITS) {
          $bits++;
        }
        $bits--;
        $shift= 32 - $bits;
        $hashtable= array_fill(0, 1 << $bits, 0);

        $start= $pos;
        $limit= $end - self::INPUT_MARGIN;
        $next= ((unpack('V', $data, ++$pos)[1] * self::HASH_KEY) & 0xffffffff) >> $shift;

        // Emit literals
        next: $forward= $pos;
        $skip= 32;
        do {
          $pos= $forward;
          $hash= $next;
          $forward+= ($skip & 0xffffffff) >> 5;
          $skip++;
          if ($pos > $limit || $forward > $limit) goto emit;

          $next= ((unpack('V', $data, $forward)[1] * self::HASH_KEY) & 0xffffffff) >> $shift;
          $candidate= $start + $hashtable[$hash];
          $hashtable[$hash]= ($pos - $start) & 0xffff;
        } while (!$equals32($pos, $candidate));

        $out.= self::literal($pos - $emit).substr($data, $emit, $pos - $emit);

        // Emit copy instructions
        do {
          $offset= $pos - $candidate;
          $matched= 4;
          while ($pos + $matched < $end && $data[$pos + $matched] === $data[$candidate + $matched]) {
            $matched++;
          }
          $pos+= $matched;

          while ($matched >= 68) {
            $out.= self::copy($offset, 64);
            $matched-= 64;
          }
          if ($matched > 64) {
            $out.= self::copy($offset, 60);
            $matched-= 60;
          }
          $out.= self::copy($offset, $matched);
          $emit= $pos;

          if ($pos >= $limit) goto emit;

          $hash= ((unpack('V', $data, $pos - 1)[1] * self::HASH_KEY) & 0xffffffff) >> $shift;
          $hashtable[$hash]= ($pos - 1 - $start) & 0xffff;
          $hash= ((unpack('V', $data, $pos)[1] * self::HASH_KEY) & 0xffffffff) >> $shift;
          $candidate= $start + $hashtable[$hash];
          $hashtable[$hash]= ($pos - $start) & 0xffff;
        } while ($equals32($pos, $candidate));

        $pos++;
        $next= ((unpack('V', $data, $pos)[1] * self::HASH_KEY) & 0xffffffff) >> $shift;
        goto next;
      }

      emit: if ($emit < $end) {
        $out.= self::literal($end - $emit).substr($data, $emit, $end - $emit);
      }
    }

    return $out;
  }

  /** Decompresses bytes */
  public function decompress(string $bytes): string {
    $out= '';
    $pos= 0;

    // Read uncompressed length from varint
    for ($length= $shift= 0, $c= 255; $shift < 32, $c >= 128; $pos++, $shift+= 7) {
      $c= ord($bytes[$pos]);
      $length|= ($c & 0x7f) << $shift;
    }

    // Decompress using literal and copy operations
    $limit= strlen($bytes);
    while ($pos < $limit) {
      $c= ord($bytes[$pos++]);
      switch ($c & 0x03) {
        case 0:
          $l= $c >> 2;
          if ($l >= 60) {
            $n= $l - 59;
            if ($pos + $n >= $limit) throw new IOException('Not enough input, expected '.$n);
            $l= unpack('P', str_pad(substr($bytes, $pos, $n), 8, "\0"))[1];
            $pos+= $n;
          }

          $l++;
          if ($pos + $l > $limit) throw new IOException('Not enough input, expected '.$l);

          $out.= substr($bytes, $pos, $l);
          $pos+= $l;
          break;

        case 1:
          $l= 4 + (($c >> 2) & 0x7);
          $offset= ord($bytes[$pos]) + (($c >> 5) << 8);
          for ($i= 0, $end= strlen($out) - $offset; $i < $l; $i++) {
            $out.= $out[$end + $i];
          }
          $pos++;
          break;

        case 2:
          if ($pos + 1 >= $limit) throw new IOException('Not enough input, expected 1');

          $l= 1 + ($c >> 2);
          $offset= unpack('v', $bytes, $pos)[1];
          for ($i= 0, $end= strlen($out) - $offset; $i < $l; $i++) {
            $out.= $out[$end + $i];
          }
          $pos+= 2;
          break;

        case 3:
          if ($pos + 3 >= $limit) throw new IOException('Not enough input, expected 3');

          $l= 1 + ($c >> 2);
          $offset= unpack('V', $bytes, $pos)[1];
          for ($i= 0, $end= strlen($out) - $offset; $i < $l; $i++) {
            $out.= $out[$end + $i];
          }
          $pos+= 4;
          break;
      }
    }

    // Verify uncompressed length
    if ($length !== ($l= strlen($out))) {
      throw new IOException('Expected length '.$length.', have '.$l);
    }

    return $out;
  }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return new SnappyInputStream($in);
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, $options= null): OutputStream {
    if (null !== ($length= Options::from($options)->length)) {
      return new SnappyOutputStream($out, $length);
    } else {
      return new BufferedOutputStream($out, [$this, 'compress']);
    }
  }
}