<?php namespace io\streams\compress\unittest;

use io\streams\{MemoryOutputStream, OutputStream};
use lang\IllegalArgumentException;
use unittest\{Assert, Before, Expect, PrerequisitesNotMetError, Test};
use util\Bytes;

abstract class CompressingOutputStreamTest {

  /** Get filter we depend on */
  protected abstract function filter(): string;

  /** Create a fixture */
  protected abstract function fixture(OutputStream $wrapped, int $level): OutputStream;

  /** Compress data */
  protected abstract function compress(string $in, int $level): string ;

  /**
   * Asserts compressed data equals. Used util.Bytes objects in
   * comparison to prevent binary data from appearing in assertion 
   * failure message.
   *
   * @param  string $expected
   * @param  string $actual
   * @throws unittest.AssertionFailedError
   */
  protected function assertCompressedDataEquals($expected, $actual) {
    Assert::equals(new Bytes($expected), new Bytes($actual));
  }

  #[Before]
  public function verifyExtensionLoaded() {
    $depend= $this->filter();
    if (!in_array($depend, stream_get_filters())) {
      throw new PrerequisitesNotMetError(ucfirst($depend).' stream filter not available', null, [$depend]);
    }
  }

  #[Test]
  public function single_write() {
    $out= new MemoryOutputStream();
    $fixture= $this->fixture($out, 6);
    $fixture->write('Hello');
    $fixture->close();

    $this->assertCompressedDataEquals($this->compress('Hello', 6), $out->bytes());
  }

  #[Test]
  public function multipe_writes() {
    $out= new MemoryOutputStream();
    $fixture= $this->fixture($out, 6);
    $fixture->write('Hello');
    $fixture->write(' ');
    $fixture->write('World');
    $fixture->close();

    $this->assertCompressedDataEquals($this->compress('Hello World', 6), $out->bytes());
  }

  #[Test, Values([1, 2, 6, 9])]
  public function at_level($level) {
    $out= new MemoryOutputStream();
    $fixture= $this->fixture($out, $level);
    $fixture->write('Hello');
    $fixture->close();

    $this->assertCompressedDataEquals($this->compress('Hello', $level), $out->bytes());
  }

  #[Test, Values([10, -1]), Expect(IllegalArgumentException::class)]
  public function level_out_of_bounds($level) {
    $this->fixture(new MemoryOutputStream(), $level);
  }

  #[Test]
  public function closing_right_after_creation() {
    $fixture= $this->fixture(new MemoryOutputStream(), 1);
    $fixture->close();
  }

  #[Test]
  public function closing_twice_has_no_effect() {
    $fixture= $this->fixture(new MemoryOutputStream(), 1);
    $fixture->close();
    $fixture->close();
  }
}