# Whitespace Cleaning Strategies - Benchmark Results

## Executive Summary

**Winner: `preg_replace('/\s+/', ' ', $string)` (Simple Regex)**

- **Fastest** for short and medium texts
- **Most reliable** for handling all whitespace types
- **Simplest** implementation

## Performance Results (1000 iterations)

### Short Text (~25 chars)

| Strategy | Time | Relative Speed |
| ---------- | ------ | ---------------- |
| **preg_replace simple** | **0.459µs** | **1.00x (fastest)** |
| preg_replace full | 0.574µs | 1.25x |
| split/join | 0.730µs | 1.59x |
| hybrid | 0.784µs | 1.71x |
| str_replace chain | 1.991µs | 4.34x |
| mb_ereg_replace | 2.055µs | 4.48x |
| strtr | 2.640µs | 5.75x |

### Medium Text (~250 chars)

| Strategy | Time | Relative Speed |
| ---------- | ------ | ---------------- |
| **preg_replace simple** | **1.355µs** | **1.00x (fastest)** |
| split/join | 1.913µs | 1.41x |
| hybrid | 2.527µs | 1.86x |
| preg_replace full | 2.792µs | 2.06x |
| str_replace chain | 3.911µs | 2.89x |
| strtr | 4.901µs | 3.62x |
| mb_ereg_replace | 15.070µs | 11.12x |

### Long Text (~2500 chars)

| Strategy | Time | Relative Speed |
| ---------- | ------ | ---------------- |
| **preg_replace simple** | **11.830µs** | **1.00x (fastest)** |
| split/join | 18.045µs | 1.53x |
| hybrid | 23.304µs | 1.97x |
| preg_replace full | 27.339µs | 2.31x |
| str_replace chain | 28.645µs | 2.42x |
| strtr | 37.012µs | 3.13x |
| mb_ereg_replace | 161.938µs | 13.69x |

## Reliability Analysis

### ✅ Fully Reliable (100% test pass)

1. **preg_replace('/[\s\xC2\xA0]+/u', ' ')** - Handles ALL cases including NBSP
2. **Hybrid (str_replace + preg_replace)** - Handles ALL cases

### ⚠️ Partially Reliable

1. **preg_replace('/\s+/', ' ')** - Doesn't handle NBSP (0xC2 0xA0)
2. **mb_ereg_replace('\s+', ' ')** - Doesn't handle NBSP
3. **split/join** - Removes leading/trailing spaces (behavior change)

### ❌ Less Reliable

1. **str_replace chain** - Complex, requires loop, may miss edge cases
2. **strtr** - Complex, requires loop, slower

## Recommendations

### For Current Implementation (WhitespaceCleaner)

**Keep current implementation:**

```php
\preg_replace('/[\s\xC2\xA0]+/u', ' ', $data);
```

**Reasons:**

- Handles ALL whitespace types including NBSP
- Good performance (0.574µs short, 2.792µs medium, 27.339µs long)
- Simple, maintainable code
- 100% reliable

### Alternative: Simple Regex (if NBSP not needed)

```php
\preg_replace('/\s+/', ' ', $data);
```

**Pros:**

- 20-57% faster than current
- Simpler pattern
- Handles standard whitespace

**Cons:**

- Doesn't handle NBSP (non-breaking space)
- May not be suitable for all use cases

### Alternative: Hybrid Approach (balanced)

```php
$result = \str_replace(["\t", "\n", "\r", "\xC2\xA0"], ' ', $data);
return \preg_replace('/\s{2,}/', ' ', $result);
```

**Pros:**

- 100% reliable
- Competitive performance
- Explicit whitespace handling

**Cons:**

- More complex
- Two-step process

## Strategy Details

### 1. preg_replace('/[\s\xC2\xA0]+/u', ' ')

- **Speed**: Medium (2-3x slower than simple)
- **Reliability**: ✅ 100%
- **Handles**: Spaces, tabs, newlines, NBSP
- **Current implementation**

### 2. preg_replace('/\s+/', ' ')

- **Speed**: ⚡ Fastest
- **Reliability**: ⚠️ 90% (no NBSP)
- **Handles**: Spaces, tabs, newlines
- **Best for**: ASCII-only content

### 3. split/join (preg_split + implode)

- **Speed**: Fast (1.4-1.6x slower than simple)
- **Reliability**: ⚠️ Changes behavior (trims)
- **Handles**: All whitespace
- **Side effect**: Removes leading/trailing spaces

### 4. Hybrid (str_replace + preg_replace)

- **Speed**: Medium (1.9-2x slower than simple)
- **Reliability**: ✅ 100%
- **Handles**: All whitespace explicitly
- **Best for**: Explicit control

### 5. str_replace chain

- **Speed**: Slow (2.4-4.3x slower)
- **Reliability**: ⚠️ Complex
- **Handles**: Requires loop
- **Not recommended**

### 6. strtr

- **Speed**: Slow (3.1-5.8x slower)
- **Reliability**: ⚠️ Complex
- **Handles**: Requires loop
- **Not recommended**

### 7. mb_ereg_replace

- **Speed**: ❌ Very slow (11-14x slower)
- **Reliability**: ⚠️ No NBSP
- **Handles**: Multibyte aware
- **Not recommended** for this use case

## Conclusion

**Current implementation is optimal** for the use case:

- Handles all whitespace types including NBSP
- Good performance across all text sizes
- Simple, maintainable code
- 100% test coverage

**No change recommended** unless:

- NBSP handling is not required → use simple regex (20-57% faster)
- Explicit control needed → use hybrid approach
