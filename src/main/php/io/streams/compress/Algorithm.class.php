<?php namespace io\streams\compress;

use io\streams\{Compression, InputStream, OutputStream};

interface Algorithm {

  /** Returns whether this algorithm is supported in the current setup */
  public function supported(): bool;

  /** Returns the algorithm's name */
  public function name(): string;

  /** Returns the algorithm's HTTP Content-Encoding token */
  public function token(): string;

  /** Returns the algorithm's common file extension, including a leading "." */
  public function extension(): string;

  /** Opens an input stream for reading */
  public function open(InputStream $in): InputStream;

  /** Opens an output stream for writing */
  public function create(OutputStream $out, int $method= Compression::DEFAULT): OutputStream;
}