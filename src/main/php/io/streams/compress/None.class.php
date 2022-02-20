<?php namespace io\streams\compress;

use io\streams\{InputStream, OutputStream};

class None implements Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return true; }

  /** Returns the algorithm's name */
  public function name(): string { return 'none'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'identity'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return ''; }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return $in;
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, int $method): OutputStream {
    return $out;
  }
}