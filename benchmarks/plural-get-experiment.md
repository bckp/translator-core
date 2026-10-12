# Scalar catalogue lookup experiment

This experiment moves plural resolution into the runtime catalogue. `get()` returns `string|null`; source input still uses `PluralMessage`. An optional quantity accepts the same scalar types as the translator, and optional caller-supplied diagnostics preserve warnings without mutating a shared catalogue.

The compiled catalogue uses a generated `match` for ordinary messages and constant string arrays for plural variants. Cache format keys change to avoid loading older incompatible method signatures.

## Warm translation

Baseline: core commit `600d21a6cc3eb92a3130bffb53a61749657cb685`. Both versions used identical benchmark subjects, including the added numeric-format and missing-key cases. PHP 8.5.11, PHPBench 1.7.0, CPU 4 pinned, OPcache verified, JIT/Xdebug off, 100,000 warmup repetitions and 1,000,000 measured repetitions per iteration. Two runs per version in baseline/candidate/candidate/baseline order; values below are medians over 40 iterations.

| Subject | Baseline (ns) | Candidate (ns) | Time change |
|---|---:|---:|---:|
| Plain text | 76.44 | 79.69 | +4.2% |
| Formatted string | 236.37 | 242.38 | +2.5% |
| Formatted number | 250.41 | 271.89 | +8.6% |
| Missing key | 76.97 | 84.74 | +10.1% |
| Plural | 438.08 | 391.20 | -10.7% |

Positive changes mean slower execution. These are local measurements on a shared machine; small differences should not be treated as portable guarantees. Source loader and version callbacks stayed at zero during measured translations.

## First catalogue initialization

A separate probe builds 1,000 ordinary messages plus the indicated number of plural entries. Each sample runs in a fresh PHP process, preloads common dependencies and compiles the generated file into OPcache before timing its first `require`. Ten samples per version. Parsing/compilation is excluded; the timed include still performs normal PHP loading. Heap deltas exclude shared OPcache memory.

| Plural entries | Baseline (µs) | Candidate (µs) | Baseline heap delta | Candidate heap delta |
|---|---:|---:|---:|---:|
| 10 | 29.92 | 25.48 | 43,416 B | 768 B |
| 1,000 | 276.14 | 37.76 | 488,536 B | 768 B |

The candidate removes per-process plural-object creation and mutation/copying of the static message array. This helps initialization and request heap use, especially with many plural entries. It does not establish an overall application speedup; that depends on catalogue size, request lifetime and translation mix.

## Validation

- Core: 8 tests on PHP 8.4.26 and 8.5.11, including memory/compiled parity, numeric quantities, empty strings, fallbacks, diagnostic isolation and legacy-cache exclusion.
- Nette: 5 tests; NEON: 2 tests, using the candidate core.
- PHPStan level 8 and PER3 with tabs pass on both PHP versions.
- Psalm 6.20.0 reports the same 37 findings on the baseline and candidate: unused public API/classes, redundant casts and `__clone` return-type diagnostics. It remains the optional checker; these findings were not suppressed.

The API experiment is retained separately because warm plain, numeric-format and missing-key lookups regress. The current `nextgen` API is preserved pending a decision on this tradeoff.
