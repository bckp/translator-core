# Compile-time default normalization experiment

Measured on 2026-10-14 against Core v3.0.0. The experiment retains `setNormalizeCallback()` and the existing abstract `Catalogue` contract.

Built-in storages implement the optional `FormattedCatalogue` interface. The default normalizer runs at compilation, and only changed texts or plural entries are retained in `Formats`. Custom callbacks still run once per formatted translation on the original selected text, including after catalogue replacement. Existing catalogue implementations use runtime normalization. Plural rules and raw lookup code are unchanged.

## Results

Median nanoseconds per translation. Negative percentages mean less elapsed time, not an equivalent percentage increase in throughput.

| Subject | PHP 8.4 baseline → experiment | Change | PHP 8.5 baseline → experiment | Change |
|---|---:|---:|---:|---:|
| Plain text | 83.40 → 82.97 ns | -0.5% | 78.55 → 79.36 ns | +1.0% |
| Formatted text | 226.84 → 202.65 ns | -10.7% | 224.05 → 187.32 ns | -16.4% |
| Formatted number | 245.84 → 211.89 ns | -13.8% | 243.06 → 194.47 ns | -20.0% |
| Form token (%label) | 289.86 → 184.78 ns | -36.3% | 295.92 → 174.93 ns | -40.9% |
| Plural, integer quantity | 359.28 → 293.91 ns | -18.2% | 347.79 → 282.47 ns | -18.8% |
| Plural fallback | 358.47 → 300.93 ns | -16.1% | 342.59 → 287.84 ns | -16.0% |
| Custom callback, formatted text | 249.56 → 248.03 ns | -0.6% | 242.28 → 253.71 ns | +4.7% |
| Custom callback, form token | 304.67 → 304.57 ns | -0.0% | 315.88 → 310.86 ns | -1.6% |

The custom formatted-text case on PHP 8.5 measured +4.7%, with overlapping sample quartiles and substantial variation between repeats. The measurements do not establish a consistent improvement or regression for custom callbacks. Small differences in unchanged plain, missing-key and raw plural paths also overlap the observed variability.

## Generated-script memory

For 1,000 messages with 300 plurals and one text requiring normalization, OPcache memory for the generated PHP script grows from 260,376 to 261,688 bytes: 254.3 → 255.6 KiB (+0.5%). The cache file grows from 109,252 to 109,825 bytes. Unlike literal plural match arms, the additional memory follows texts changed by normalization. A catalogue with more form tokens will retain more normalized text.

## Method

PHPBench 1.7.0 on PHP 8.4.26 and 8.5.11; CPU 4, OPcache enabled, JIT and Xdebug disabled, production diagnostics disabled. Synthetic catalogue: 1,000 keys, 10 plurals, one ordinary text requiring normalization. Two runs per variant, ordered baseline–experiment–experiment–baseline for each PHP version. Each run uses 15 iterations of 250,000 calls and 25,000 warm-up calls: 30 samples per variant. Catalogue initialization and compilation are excluded; method and loop overhead are included. Source load/version counters remain zero during translation. The custom callback delegates to the unchanged `Translator::normalize()`.

Baseline: `v3.0.0; same tree as 792e66149d87d0849b6bfb952257c179950d1e84`. Measured source subtree: `43cd4e9aa8cc1470d52e52e1d7553989b7387796`. Every subject is in [the CSV](normalization-2026-10-14.csv). The repository's `benchNormalized` subject measures the form-token path; `composer benchmark` runs the maintained microbenchmarks.

## Validation

Core: 11 tests on PHP 8.4 and 8.5, including normalized plural fallbacks, empty strings, named parameters, stateful callbacks, replacement and legacy catalogues. Nette and NEON integration suites passed against the experimental core. PHPStan level 8 and PER3 with tabs passed. Roave reports no incompatible changes against v3.0.0. PHP cache format 7 rebuilds old cached catalogues; no new runtime Composer dependency is added.
