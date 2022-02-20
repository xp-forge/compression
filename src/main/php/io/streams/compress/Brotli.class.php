<?php namespace io\streams\compress;

use io\streams\{InputStream, OutputStream, Compression};

class Brotli implements Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return extension_loaded('brotli'); }

  /** Returns the algorithm's name */
  public function name(): string { return 'brotli'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'br'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return '.br'; }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return new BrotliInputStream($in);
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, int $method): OutputStream {
    static $levels= [Compression::FASTEST => 1, Compression::DEFAULT => 11, Compression::STRONGEST => 11];

    return new BrotliOutputStream($out, $levels[$method]);
  }
}