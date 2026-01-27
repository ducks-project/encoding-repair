# Project Structure

## Directory Organization

```text
encoding-repair/
├── Cache/                      # PSR-16 cache implementations
│   ├── ArrayCache.php         # Full-featured PSR-16 with TTL support
│   └── InternalArrayCache.php # Optimized cache without TTL overhead
├── Detector/                   # Encoding detection strategies
│   ├── CachedDetector.php     # Decorator with PSR-16 cache support
│   ├── CallableDetector.php   # Adapter for legacy callable detectors
│   ├── DetectorChain.php      # Chain of Responsibility coordinator
│   ├── DetectorInterface.php  # Contract for detection strategies
│   ├── FileInfoDetector.php   # Detection using ext-fileinfo (priority: 50)
│   └── MbStringDetector.php   # Detection using ext-mbstring (priority: 100)
├── Interpreter/                # Type-specific processing strategies
│   ├── ArrayInterpreter.php   # Recursive array processing (priority: 50)
│   ├── InterpreterChain.php   # Chain of Responsibility for interpreters
│   ├── ObjectInterpreter.php  # Object processing with property mapping (priority: 30)
│   ├── PropertyMapperInterface.php # Contract for custom property mapping
│   ├── StringInterpreter.php  # String processing (priority: 100)
│   └── TypeInterpreterInterface.php # Contract for type interpreters
├── Traits/                     # Reusable behavior components
│   ├── CallableAdapterTrait.php # Common logic for callable adapters
│   └── ChainOfResponsibilityTrait.php # Generic CoR implementation
├── Transcoder/                 # Encoding conversion strategies
│   ├── CallableTranscoder.php # Adapter for legacy callable transcoders
│   ├── IconvTranscoder.php    # Conversion using ext-iconv (priority: 50)
│   ├── MbStringTranscoder.php # Conversion using ext-mbstring (priority: 10)
│   ├── TranscoderChain.php    # Chain of Responsibility coordinator
│   ├── TranscoderInterface.php # Contract for conversion strategies
│   └── UConverterTranscoder.php # Conversion using ext-intl (priority: 100)
├── tests/
│   ├── Benchmark/             # PHPBench performance tests
│   ├── Common/                # Test fixtures and utilities
│   └── Phpunit/               # PHPUnit unit tests
├── docs/                       # ReadTheDocs documentation source
├── examples/                   # Usage examples
├── assets/documentation/       # Generated API documentation
├── CharsetHelper.php          # Static facade (backward compatibility)
├── CharsetProcessor.php       # Service implementation
├── CharsetProcessorInterface.php # Service contract
└── PrioritizedHandlerInterface.php # Priority system contract
```

## Core Components

### 1. Service Layer

**CharsetProcessorInterface** - Service contract defining all charset operations

- Conversion methods: `toCharset()`, `toCharsetBatch()`, `toUtf8()`, `toIso()`
- Detection methods: `detect()`, `detectBatch()`
- Repair methods: `repair()`
- JSON safety: `safeJsonEncode()`, `safeJsonDecode()`
- Configuration: Transcoder/detector/interpreter/encoding management

**CharsetProcessor** - Concrete service implementation

- Fluent API for method chaining
- Manages transcoder/detector/interpreter chains
- Handles encoding whitelist
- Delegates to specialized components

**CharsetHelper** - Static facade for backward compatibility

- Lazy initialization of CharsetProcessor
- Preserves legacy API (100% backward compatible)
- Delegates all operations to processor instance

### 2. Transcoder Chain (Conversion Strategies)

**Priority-based fallback system:**

1. **UConverterTranscoder** (100) - ext-intl, best precision, 30% faster
2. **IconvTranscoder** (50) - ext-iconv, transliteration support
3. **MbStringTranscoder** (10) - ext-mbstring, universal fallback

**TranscoderChain** - Coordinates transcoder execution using SplPriorityQueue
**CallableTranscoder** - Adapter for legacy callable transcoders

### 3. Detector Chain (Encoding Detection)

**Priority-based detection system:**

1. **CachedDetector** (200) - Wraps MbStringDetector with PSR-16 cache
2. **MbStringDetector** (100) - Uses mb_detect_encoding
3. **FileInfoDetector** (50) - Uses finfo class

**DetectorChain** - Coordinates detector execution using SplPriorityQueue
**CallableDetector** - Adapter for legacy callable detectors

### 4. Interpreter Chain (Type-Specific Processing)

**Strategy + Visitor pattern for optimized type handling:**

1. **StringInterpreter** (100) - Direct string transcoding
2. **ArrayInterpreter** (50) - Recursive array processing
3. **ObjectInterpreter** (30) - Object cloning with property mapping

**InterpreterChain** - Coordinates interpreter execution
**PropertyMapperInterface** - Contract for custom object property selection

### 5. Cache Layer (PSR-16 Support)

**InternalArrayCache** - Default optimized cache without TTL overhead
**ArrayCache** - Full PSR-16 implementation with TTL support
**CachedDetector** - Decorator wrapping any detector with cache

## Architectural Patterns

### Chain of Responsibility

- **TranscoderChain**: Tries multiple conversion strategies until success
- **DetectorChain**: Tries multiple detection methods until match
- **InterpreterChain**: Delegates to type-specific handlers
- **ChainOfResponsibilityTrait**: Generic implementation with SplPriorityQueue

### Strategy Pattern

- **TranscoderInterface**: Pluggable conversion algorithms
- **DetectorInterface**: Pluggable detection algorithms
- **TypeInterpreterInterface**: Pluggable type processing strategies

### Visitor Pattern

- **InterpreterChain**: Visits data structures and delegates to type handlers
- **PropertyMapperInterface**: Visits object properties selectively

### Facade Pattern

- **CharsetHelper**: Simplified static API hiding service complexity

### Decorator Pattern

- **CachedDetector**: Adds caching behavior to any detector
- **CallableTranscoder/CallableDetector**: Adapts callables to interfaces

### Adapter Pattern

- **CallableTranscoder**: Adapts legacy callable transcoders
- **CallableDetector**: Adapts legacy callable detectors
- **CallableAdapterTrait**: Shared adapter logic

## Component Relationships

```text
CharsetHelper (Facade)
    └── CharsetProcessor (Service)
        ├── TranscoderChain
        │   ├── UConverterTranscoder
        │   ├── IconvTranscoder
        │   └── MbStringTranscoder
        ├── DetectorChain
        │   ├── CachedDetector
        │   │   └── MbStringDetector
        │   └── FileInfoDetector
        └── InterpreterChain
            ├── StringInterpreter
            ├── ArrayInterpreter
            └── ObjectInterpreter
                └── PropertyMapperInterface (custom mappers)
```

## Extension Points

1. **Custom Transcoders**: Implement `TranscoderInterface`, register with priority
2. **Custom Detectors**: Implement `DetectorInterface`, register with priority
3. **Custom Interpreters**: Implement `TypeInterpreterInterface`, register with priority
4. **Custom Property Mappers**: Implement `PropertyMapperInterface` for specific classes
5. **Custom Cache**: Provide any PSR-16 implementation to CachedDetector
6. **Multiple Processors**: Create independent instances with different configurations
