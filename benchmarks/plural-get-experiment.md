# Scalar catalogue lookup experiment

This experiment moves plural resolution into the runtime catalogue. `get()` returns `string|null`; source input still uses `PluralMessage`. An optional quantity accepts the same scalar types as the translator. The latest revision removes the diagnostics argument and uses a `DiagnosticCatalogue` wrapper, selected once at translator construction or catalogue replacement. Its separate `missingPluralForm()` lookup preserves warnings without mutating the shared catalogue. Production translations never check diagnostics.

The compiled catalogue uses a generated `match` for ordinary messages and constant string arrays for plural variants. Cache format keys change to avoid loading older incompatible method signatures.

## Two-argument getter with diagnostic wrapper

The revised translator selects its diagnostic wrapper once, returns unformatted text directly and passes only the key and quantity for formatted messages. The compiled getter no longer checks diagnostics, including when a plural form falls back. Named formatting arguments remain supported via a guarded positional lookup.

Baseline: `600d21a6cc3eb92a3130bffb53a61749657cb685`; earlier three-argument revision: `5de530d68183358c2d30e17f987b3604fdb206f1`. Measured on 2026-10-12 using PHP 8.5.11, PHPBench 1.7.0, CPU 4 pinned, OPcache verified, JIT/Xdebug off, 100,000 warmup repetitions and 1,000,000 measured repetitions per iteration. All three versions used identical subjects. Runs were ordered baseline/earlier/revised/revised/earlier/baseline; values below are medians over 40 iterations per version.

| Subject | Baseline (ns) | Three arguments (ns) | Two arguments + wrapper (ns) | Change vs baseline |
|---|---:|---:|---:|---:|
| Plain text | 81.59 | 79.85 | 72.49 | -11.2% |
| Formatted string | 239.87 | 246.37 | 225.31 | -6.1% |
| Formatted number | 250.06 | 264.09 | 239.94 | -4.0% |
| Missing key | 78.99 | 80.03 | 74.43 | -5.8% |
| Plural | 427.77 | 397.77 | 368.97 | -13.7% |

Negative changes mean faster execution. The revised implementation improved all five subjects in this comparison; it was also 7–9% faster than the three-argument revision. These are local microbenchmarks on a shared machine, not an overall application speedup or portable guarantees. Small differences remain sensitive to noise; for example, the plain-text comparison between the baseline and the earlier revision changed direction relative to the historical measurement below. Source loader and version callback counters stayed at zero throughout all six runs. The diagnostic wrapper itself was not enabled during these production-path measurements.

## Earlier three-argument revision

Baseline: core commit `600d21a6cc3eb92a3130bffb53a61749657cb685`. Both versions used identical benchmark subjects, including the added numeric-format and missing-key cases. PHP 8.5.11, PHPBench 1.7.0, CPU 4 pinned, OPcache verified, JIT/Xdebug off, 100,000 warmup repetitions and 1,000,000 measured repetitions per iteration. Two runs per version in baseline/candidate/candidate/baseline order; values below are medians over 40 iterations.

| Subject | Baseline (ns) | Candidate (ns) | Time change |
|---|---:|---:|---:|
| Plain text | 76.44 | 79.69 | +4.2% |
| Formatted string | 236.37 | 242.38 | +2.5% |
| Formatted number | 250.41 | 271.89 | +8.6% |
| Missing key | 76.97 | 84.74 | +10.1% |
| Plural | 438.08 | 391.20 | -10.7% |

Positive changes mean slower execution. These are local measurements on a shared machine; small differences should not be treated as portable guarantees. Source loader and version callbacks stayed at zero during measured translations.

## Earlier first catalogue initialization

A separate probe of the earlier three-argument revision builds 1,000 ordinary messages plus the indicated number of plural entries. Each sample runs in a fresh PHP process, preloads common dependencies and compiles the generated file into OPcache before timing its first `require`. Ten samples per version. Parsing/compilation is excluded; the timed include still performs normal PHP loading. Heap deltas exclude shared OPcache memory.

| Plural entries | Baseline (µs) | Candidate (µs) | Baseline heap delta | Candidate heap delta |
|---|---:|---:|---:|---:|
| 10 | 29.92 | 25.48 | 43,416 B | 768 B |
| 1,000 | 276.14 | 37.76 | 488,536 B | 768 B |

The candidate removes per-process plural-object creation and mutation/copying of the static message array. This helps initialization and request heap use, especially with many plural entries. It does not establish an overall application speedup; that depends on catalogue size, request lifetime and translation mix.

## Validation

- Core: 9 tests on PHP 8.4.26 and 8.5.11, including memory/compiled parity, numeric quantities, empty strings, fallbacks, diagnostic isolation, replacement, named formatting arguments and legacy-cache exclusion.
- Nette: 5 tests; NEON: 2 tests, using the candidate core.
- PHPStan level 8 and PER3 with tabs pass on both PHP versions.
- Psalm 6.20.0 reports 34 findings in the revised core, compared with 37 in the baseline and earlier revision. The revised findings are a subset of the baseline: unused public API/classes, redundant casts and `__clone` return-type diagnostics. Psalm remains optional; these findings were not suppressed.

The API experiment remains on `nextgen-plural-get`; the current `nextgen` API is preserved while the runtime API is being evaluated. A static independent review identified a named-variadic-argument regression during the wrapper refactor. Core and Nette regression tests reproduced the warning before the fix; restoring the guarded first-parameter lookup preserves the previous behavior.
