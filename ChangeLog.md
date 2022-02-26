Compression streams ChangeLog
=============================

## ?.?.? / ????-??-??

## 1.0.0 / 2022-02-26

This first release stabilizes the `io.streams.compress.Algorithm`
interface.

* Added `Algorithm::level()` which will return the fastest, default and
  strongest levels supported for the predefined `Compression::DEFAULT`,
  `Compression::FASTEST` and `Compression::STRONGEST`.
  (@thekid)
* Changed `Algorithm::create()` to accept either predefined constants for
  compression level or the level directly.
  (@thekid)

## 0.3.0 / 2022-02-25

* Merged PR #2: Make Compression::named() raise exceptions for unsupported
  algorithms
  (@thekid)
* Added string representation for `Algorithm` instances - @thekid

## 0.2.0 / 2022-02-20

* Merged PR #1: Compression API - @thekid

## 0.1.0 / 2022-02-19

* Throw exceptions when reading erroneous data (GZIP, BZ2) - @thekid
* Extracted library from XP Framework, see xp-framework/core#307 - @thekid