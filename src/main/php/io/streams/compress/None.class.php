<?php namespace io\streams\compress;

use io\streams\{Compression, InputStream, OutputStream};

class None extends Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return true; }

  /** Returns the algorithm's name */
  public function name(): string { return 'none'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'identity'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return ''; }

  /** Returns fastest, default and strongest levels */
  public function level(int $select): int { return 0; }

  /** Compresses data */
  public function compress(string $data, $options= null): string { return $data; }

  /** Decompresses bytes */
  public function decompress(string $bytes): string { return $bytes; }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return $in;
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, $options= null): OutputStream {
    return $out;
  }
}