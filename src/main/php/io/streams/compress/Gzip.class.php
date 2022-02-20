<?php namespace io\streams\compress;

use io\streams\{InputStream, OutputStream, Compression};

class Gzip implements Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return extension_loaded('zlib'); }

  /** Returns the algorithm's name */
  public function name(): string { return 'gzip'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'gzip'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return '.gz'; }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return new GzipInputStream($in);
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, int $method): OutputStream {
    static $levels= [Compression::FASTEST => 1, Compression::DEFAULT => 6, Compression::STRONGEST => 9];

    return new GzipOutputStream($out, $levels[$method]);
  }
}