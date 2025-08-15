<?php namespace io\streams\compress;

use io\IOException;
use io\streams\{InputStream, OutputStream, Compression};

class Brotli extends Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return extension_loaded('brotli'); }

  /** Returns the algorithm's name */
  public function name(): string { return 'brotli'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'br'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return '.br'; }

  /** Maps fastest, default and strongest levels */
  public function level(int $select): int {
    static $levels= [Compression::FASTEST => 1, Compression::DEFAULT => 11, Compression::STRONGEST => 11];
    return $levels[$select] ?? $select;
  }

  /** Compresses data */
  public function compress(string $data, $options= null): string {
    return brotli_compress($data, $this->level(Options::from($options)->level));
  }

  /** Decompresses bytes */
  public function decompress(string $bytes): string {
    if (false === ($data= brotli_uncompress($bytes))) {
      $e= new IOException('Decompression failed');
      \xp::gc(__FILE__);
      throw $e;
    }
    return $data;
  }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return new BrotliInputStream($in);
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, $options= null): OutputStream {
    return new BrotliOutputStream($out, $this->level(Options::from($options)->level));
  }
}