<?php namespace io\streams\compress;

use io\IOException;
use io\streams\{InputStream, OutputStream, Compression};

class ZStandard extends Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool { return extension_loaded('zstd'); }

  /** Returns the algorithm's name */
  public function name(): string { return 'zstandard'; }

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string { return 'zstd'; }

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string { return '.zstd'; }

  /** Maps fastest, default and strongest levels */
  public function level(int $select): int {
    static $levels= [Compression::FASTEST => 1, Compression::DEFAULT => 3, Compression::STRONGEST => 22];
    return $levels[$select] ?? $select;
  }

  /** Compresses data */
  public function compress(string $data, int $level= Compression::DEFAULT): string {
    return zstd_compress($data, $this->level($level));
  }

  /** Decompresses bytes */
  public function decompress(string $bytes): string {
    if (false === ($data= zstd_uncompress($bytes))) {
      $e= new IOException('Decompression failed');
      \xp::gc(__FILE__);
      throw $e;
    }
    return $data;
  }

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream {
    return new ZStandardInputStream($in);
  }

  /** Opens an output stream for writing */
  public function create(OutputStream $out, int $level= Compression::DEFAULT): OutputStream {
    return new ZStandardOutputStream($out, $this->level($level));
  }
}