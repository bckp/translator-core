# Translation benchmarks

`composer benchmark` uses PHPBench for plain, formatted and Czech plural translations, plus direct catalogue plural selection and lookup. Each case has 10 independent iterations, 100,000 calls per iteration and 10,000 warmup calls. Catalogue preparation is outside the measured translation path.

The compiled catalogue is prepared before the iterations and reused from the PHP cache. Diagnostics are disabled. After each iteration, the benchmark checks that neither loading source data nor checking its version occurred during translation.

OPcache is enabled in worker processes and verified by the bootstrap. The benchmark also verifies that the generated catalogue file is actually cached before measurement. File update protection is set to zero for these isolated benchmark workers so newly generated catalogues are optimized from the first iteration. JIT and Xdebug are disabled; a missing OPcache extension makes the benchmark fail explicitly.

## Store and compare runs

```sh
composer benchmark -- --tag=before
# Make and commit the change on the same machine and PHP configuration.
composer benchmark -- --ref=before --report=aggregate --tag=after
```

Results live in the ignored `.phpbench/storage` directory. PHPBench reports dispersion across iterations; repeat noisy measurements before interpreting small differences. Compare runs on the same hardware, PHP version, dependencies and engine configuration.

GitHub Actions runs benchmarks on PHP 8.4 and 8.5 and uploads XML results and stored runs for 30 days. Time differences do not gate PRs on shared runners; execution errors, missing OPcache and unexpected source calls fail the job.

## Current nextgen versus 2.x

Measured on 2026-10-13: `v2.0.0` (`ab20317018670b96993b0b36f379d9f3b9cd0505`) versus `nextgen` (`2e9e04d5da0665d64a365a946ecaf476344c02b9`). These results include the scalar catalogue getter, production diagnostics separation and embedded plural rules. Earlier development measurements do not describe this revision.

PHPBench 1.7.0 ran on the same AMD EPYC 9V74 host, pinned to CPU 4, with OPcache verified as enabled and the generated script cached. JIT, Xdebug and translation diagnostics were disabled. Each PHP version and catalogue size ran in v2/nextgen/nextgen/v2 order: 20 iterations per run, 1,000,000 calls per iteration and 100,000 warmup calls before each iteration. Each table entry is the median of 40 samples per version.

Both versions used their actual PHP cache compilers without changing their namespaces. Before each iteration, the harness verified identical output for every translation scenario. After each iteration, it checked that source loading and version verification stayed at zero during measurement. Catalogue preparation and initialization are excluded; the common benchmark-method and loop overhead is included.

The tables below use a synthetic catalogue with 1,000 entries: three ordinary test messages, two plural messages and 995 ordinary padding messages. Measured entries are registered near the middle of the catalogue. The formatted string uses `Ada`; numeric formatting and the Czech plural rule use quantity 3. The fallback case omits `few` and resolves to `other` in both versions.

### PHP 8.4.26

| Case | 2.x (ns/call) | Nextgen (ns/call) | Time change |
| --- | ---: | ---: | ---: |
| Plain text | 88.6 | 83.4 | -5.8% |
| Formatted string | 270.0 | 229.1 | -15.1% |
| Formatted number | 281.2 | 239.4 | -14.8% |
| Missing key | 98.4 | 77.7 | -21.0% |
| Czech plural with formatting | 438.7 | 327.8 | -25.3% |
| Missing plural form with fallback | 604.4 | 337.3 | -44.2% |
| Plural without quantity | 156.9 | 128.4 | -18.2% |
| Catalogue plural selection | 72.7 | 51.6 | -29.0% |

### PHP 8.5.11

| Case | 2.x (ns/call) | Nextgen (ns/call) | Time change |
| --- | ---: | ---: | ---: |
| Plain text | 81.0 | 77.3 | -4.7% |
| Formatted string | 257.6 | 230.4 | -10.6% |
| Formatted number | 291.8 | 254.3 | -12.8% |
| Missing key | 94.1 | 76.7 | -18.5% |
| Czech plural with formatting | 469.7 | 376.0 | -20.0% |
| Missing plural form with fallback | 653.7 | 356.2 | -45.5% |
| Plural without quantity | 145.5 | 121.8 | -16.3% |
| Catalogue plural selection | 69.1 | 49.8 | -27.9% |

Negative percentages mean less time per call, not a percentage increase in throughput. The same comparison with five entries measured lower median times for nextgen in all eight cases on both runtimes. [CSV results](v2-nextgen-2026-10-13.csv) contain both catalogue sizes.

Small differences on the plain-text path are sensitive to variation on the shared host. Formatting and plural translation improved in both runs on both runtimes. These measurements describe warm catalogue calls; they do not measure full requests, source reloads, cold compilation, diagnostics or every message distribution. Compare implementations within the same PHP runtime; the PHP 8.4 and 8.5 Docker images have different builds.

### Generated catalogue size

| Metric, 1,000 entries | 2.x | Nextgen |
| --- | ---: | ---: |
| PHP file on disk | 46.1 KiB | 49.7 KiB |
| Generated script in OPcache | 42.1 KiB | 127.0 KiB |

The OPcache figures are the generated script's `memory_consumption` reported by `opcache_get_status()`, rounded from 43,136 bytes versus 130,024 bytes on PHP 8.4 and 130,040 bytes on PHP 8.5. They are not total application memory. Generated `match` methods use more OPcache memory than the 2.x array-based catalogue.

## Lightweight benchmark

The standalone script remains available as `composer benchmark-simple`, or:

```sh
php -d opcache.enable_cli=1 benchmarks/translate.php 1000000
```

It provides a quick check of the current implementation with five rounds per case. Use PHPBench for repeated comparisons and dispersion; this script does not reproduce the v2 comparison above.
