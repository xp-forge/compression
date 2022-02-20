<?php namespace io\streams\compress;

use io\streams\{InputStream, OutputStream, Compression};

class Bzip2 implements Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return extension_loaded('bzip2'); }

  /** Returns the algorithm's name */
  public function name(): string { return 'bzip2'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'bzip2'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return '.bz2'; }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return new Bzip2InputStream($in);
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, int $method): OutputStream {
    static $levels= [Compression::FASTEST => 1, Compression::DEFAULT => 4, Compression::STRONGEST => 9];

    return new Bzip2OutputStream($in, $levels[$method]);
  }
}