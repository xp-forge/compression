<?php namespace io\streams\compress;

use io\IOException;
use io\streams\{InputStream, OutputStream, Compression};

class Bzip2 extends Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return extension_loaded('bz2'); }

  /** Returns the algorithm's name */
  public function name(): string { return 'bzip2'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'bzip2'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return '.bz2'; }

  /** Maps fastest, default and strongest levels */
  public function level(int $select): int {
    static $levels= [Compression::FASTEST => 1, Compression::DEFAULT => 4, Compression::STRONGEST => 9];
    return $levels[$select] ?? $select;
  }

  /** Compresses data */
  public function compress(string $data, $options= null): string {
    return bzcompress($data, $this->level(Options::from($options)->level));
  }

  /** Decompresses bytes */
  public function decompress(string $bytes): string {
    if (is_string($data= bzdecompress($bytes))) return $data;

    $e= new IOException('Decompression failed ('.(false === $data ? 'general error' : 'error #'.$data).')');
    \xp::gc(__FILE__);
    throw $e;
  }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return new Bzip2InputStream($in);
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, $options= null): OutputStream {
    return new Bzip2OutputStream($out, $this->level(Options::from($options)->level));
  }
}