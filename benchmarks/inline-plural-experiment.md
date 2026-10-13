# Embedded plural rule experiment

The PHP cache compiler copies the selected method body from `PluralProvider` into the generated catalogue's `plural()` method. Reflection locates the method and PHP tokens locate its braces; the rule itself is maintained only in the provider. The generated namespace imports `Plural` to preserve enum references. Source IO occurs only during compilation.

Baseline: core `nextgen` commit `1f830deb18e243dd1c28766995fad1275ece05d0`. Both versions use identical benchmark subjects, including the two added direct catalogue calls. The revised source-reader cleanup does not change the generated method body or measured runtime code.

## Warm translation

PHPBench 1.7.0, CPU 4 pinned, OPcache verified, JIT/Xdebug off, 100,000 warmup repetitions and 1,000,000 repetitions per iteration. Each runtime used baseline/candidate/candidate/baseline order, with 20 iterations per run. Tables report medians over 40 samples per version, measured on 2026-10-13. Source loader and version callback counters stayed at zero throughout all eight runs.

### PHP 8.5.11

| Subject | Baseline (ns) | Embedded rule (ns) | Time change |
|---|---:|---:|---:|
| Plain text | 74.49 | 74.52 | +0.0% |
| Formatted string | 220.64 | 224.37 | +1.7% |
| Formatted number | 237.58 | 245.15 | +3.2% |
| Missing key | 71.00 | 71.38 | +0.5% |
| Full plural translation | 373.19 | 337.93 | -9.4% |
| Catalogue plural selection | 63.63 | 47.69 | -25.0% |
| Catalogue plural lookup | 128.07 | 110.24 | -13.9% |

### PHP 8.4.26

| Subject | Baseline (ns) | Embedded rule (ns) | Time change |
|---|---:|---:|---:|
| Plain text | 78.36 | 78.43 | +0.1% |
| Formatted string | 230.51 | 225.00 | -2.4% |
| Formatted number | 242.07 | 242.35 | +0.1% |
| Missing key | 76.80 | 76.65 | -0.2% |
| Full plural translation | 362.13 | 338.73 | -6.5% |
| Catalogue plural selection | 66.71 | 50.25 | -24.7% |
| Catalogue plural lookup | 138.12 | 130.03 | -5.9% |

Negative changes mean faster execution. Plural selection improved in both runs on both runtimes; the complete plural translation improved by 6.5–9.4% in the pooled comparison. Plain translation stayed close to the baseline. The formatted/missing controls do not invoke the modified rule; their small differences varied by runtime and run, including the +3.2% numeric-format result on PHP 8.5. These are local microbenchmarks on a shared machine, not portable performance guarantees or an overall application speedup. The lookup cases use the Czech rule and quantity 3; they do not establish timings for every rule or quantity.

## Validation

- Core: 10 tests, PHPStan level 8 and PER3 with tabs pass on PHP 8.4.26 and 8.5.11.
- The standalone-cache test compiles all three rules, then loads them in a fresh process where every provider selector throws. It verifies selection and catalogue lookups against the original rules, including integer boundaries, negative quantities, numeric strings, floats and missing-form diagnostics.
- The standalone test failed on the original static provider call and passed after embedding the bodies.
- Nette: 5 tests and PHPStan; NEON: 2 tests and PHPStan pass with the candidate core on PHP 8.5.
- Package metadata validates; the PHP cache package now requires the tokenizer extension. No parser library or runtime core dependency was added.

Cache format 6 excludes older files before including them, ensuring existing deployments rebuild with the embedded rules. The method body participates in the generated class hash. Later provider changes are captured when the catalogue is rebuilt.

The embedded plural rules are now part of `nextgen`. The `nextgen-inline-plural` branch retains the measured experiment revision, and the baseline commit above preserves the earlier implementation for comparison.
