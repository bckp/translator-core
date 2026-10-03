# Translation benchmark

Run `composer benchmark`, or increase the number of translations in each round:

```sh
php -d opcache.enable_cli=1 benchmarks/translate.php 1000000
```

The script runs five rounds per case and reports the median in nanoseconds per translation. It measures a warm compiled catalogue with diagnostics disabled and checks that neither the source loader nor its version callback runs during translation.

The initial 3.0 comparison used PHP 8.4.26 with CLI OPcache enabled on the same machine, sequentially, with five rounds of 1,000,000 translations per case. The 2.x source was taken from commit `ab20317018670b96993b0b36f379d9f3b9cd0505` and used the same messages, parameters and loop.

| Case | 2.x | 3.0 |
| --- | ---: | ---: |
| Plain string | 112.8 ns | 117.5 ns |
| Formatted string | 353.9 ns | 334.7 ns |
| Czech plural with formatting | 582.6 ns | 577.7 ns |

These are microbenchmark results, not request latency or a cross-machine performance guarantee. The plain path measured about 4% slower; formatting about 5% faster; plural translation was similar. The 3.0 run performed 15 million translations with zero source calls and zero version checks.
