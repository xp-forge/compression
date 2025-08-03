<?php namespace io\streams\compress\unittest;

use io\streams\Compression;
use io\streams\compress\Options;
use test\{Assert, Test, Values};

class OptionsTest {
  const LENGTH= 6100;

  /** @return iterable */
  private function maps() {
    yield [[], Compression::DEFAULT, null];
    yield [['unused' => -1], Compression::DEFAULT, null];
    yield [['level' => Compression::FASTEST], Compression::FASTEST, null];
    yield [['level' => Compression::FASTEST, 'length' => self::LENGTH], Compression::FASTEST, self::LENGTH];
  }

  #[Test]
  public function can_create() {
    new Options();
  }

  #[Test]
  public function default_level() {
    Assert::equals(Compression::DEFAULT, (new Options())->level);
  }

  #[Test]
  public function default_length() {
    Assert::null((new Options())->length);
  }

  #[Test, Values([Compression::FASTEST, Compression::DEFAULT, Compression::STRONGEST])]
  public function level($level) {
    Assert::equals($level, (new Options($level))->level);
  }

  #[Test]
  public function length() {
    Assert::equals(self::LENGTH, (new Options(null, self::LENGTH))->length);
  }

  #[Test]
  public function from_level() {
    Assert::equals(new Options(Compression::FASTEST, null), Options::from(Compression::FASTEST));
  }

  #[Test]
  public function from_null() {
    Assert::equals(new Options(Compression::DEFAULT, null), Options::from(null));
  }

  #[Test]
  public function from_options() {
    $options= new Options(Compression::FASTEST, self::LENGTH);
    Assert::equals($options, Options::from($options));
  }

  #[Test, Values(from: 'maps')]
  public function from($map, $level, $length) {
    Assert::equals(new Options($level, $length), Options::from($map));
  }

  #[Test]
  public function string_representation() {
    Assert::equals(
      'io.streams.compress.Options(level: DEFAULT, length: null)',
      (new Options())->toString()
    );
  }

  #[Test]
  public function string_representation_with_length() {
    Assert::equals(
      'io.streams.compress.Options(level: DEFAULT, length: 6100)',
      (new Options(null, self::LENGTH))->toString()
    );
  }

  #[Test]
  public function string_representation_with_level() {
    Assert::equals(
      'io.streams.compress.Options(level: 22, length: null)',
      (new Options(22))->toString()
    );
  }
}