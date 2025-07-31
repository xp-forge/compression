<?php namespace io\streams\compress;

use io\streams\{InputStream, OutputStream, Compression};

class Gzip extends Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return extension_loaded('zlib'); }

  /** Returns the algorithm's name */
  public function name(): string { return 'gzip'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'gzip'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return '.gz'; }

  /** Maps fastest, default and strongest levels */
  public function level(int $select): int {
    static $levels= [Compression::FASTEST => 1, Compression::DEFAULT => 6, Compression::STRONGEST => 9];
    return $levels[$select] ?? $select;
  }

  /** Compresses data */
  public function compress(string $data, int $level= Compression::DEFAULT): string {
    return gzcompress($data, $this->level($level));
  }

  /** Decompresses bytes */
  public function decompress(string $bytes): string {
    return gzuncompress($bytes);
  }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return new GzipInputStream($in);
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, int $level= Compression::DEFAULT): OutputStream {
    return new GzipOutputStream($out, $this->level($level));
  }
}