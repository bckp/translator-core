# Translation benchmark

Run `composer benchmark`, or increase the number of translations in each round:

```sh
php -d opcache.enable_cli=1 benchmarks/translate.php 1000000
```

The script runs five rounds per case and reports the median in nanoseconds per translation. It measures a warm compiled catalogue with diagnostics disabled and checks that neither the source loader nor its version callback runs during translation.

The script prints the PHP version and the actual OPcache and JIT status. Setting `opcache.enable_cli=1` only enables OPcache when its extension is loaded.

The initial 3.0 comparison used PHP 8.4.26 with OPcache disabled on the same machine, sequentially, with five rounds of 1,000,000 translations per case. It was initially described incorrectly as having OPcache enabled: the extension was installed but not loaded. The 2.x source was taken from commit `ab20317018670b96993b0b36f379d9f3b9cd0505` and used the same messages, parameters and loop.

| Case | 2.x | 3.0 |
| --- | ---: | ---: |
| Plain string | 112.8 ns | 117.5 ns |
| Formatted string | 353.9 ns | 334.7 ns |
| Czech plural with formatting | 582.6 ns | 577.7 ns |

That run measured the plain path about 4% slower, formatting about 5% faster and plural translation similarly. It performed 15 million translations with zero source calls and zero version checks.

A follow-up comparison loaded both versions into one PHP 8.4.26 process with OPcache verified as enabled and JIT disabled. The original namespace was renamed to avoid class collisions; both translators used compiled PHP catalogues, the same messages and the same measurement loop. Each case ran eight rounds of 1,000,000 translations, alternating translator order on each round. Plain translation was warmed up with 20,000 calls per translator before measurement. The table reports medians.

| Case | 2.x | 3.0 before optimization | 3.0 after optimization |
| --- | ---: | ---: | ---: |
| Plain string | 93.4 ns | 104.6 ns | 96.9 ns |
| Formatted string | 325.9 ns | 298.6 ns | 260.8 ns |
| Czech plural with formatting | 519.3 ns | 511.4 ns | 509.9 ns |

The optimized translator returns a plain string immediately when there are no parameters, moves plural selection into a separate method and explicitly imports the global PHP functions it uses. Missing translations still use an exact `null` check, preserving empty strings and `"0"` as valid translations. In this comparison, plain translation remains about 3.5 ns slower than 2.x, while formatting is faster. Source loading and version verification remain outside the translation path.

These are local microbenchmark results, not request latency or a cross-machine performance guarantee. The sequential and single-process measurements have different harnesses; compare versions within each table. PHP 8.5 performance has not been measured locally.
