# Glossary

Complete reference of all classes, interfaces, and documentation pages.

## Documentation Pages

- [Usage Guide](Usage.md) - Complete usage examples and patterns
- [Use Cases](UseCases.md) - Real-world usage examples
- [Advanced Usage](AdvancedUsage.md) - Extensibility and advanced features
- [Changelog](../../CHANGELOG.md) - Version history and release notes
- [How To](HowTo.md) - Practical guides and tutorials
- [About Middleware Pattern](AboutMiddleware.md) - Chain of Responsibility pattern explanation
- [Type Interpreter System](INTERPRETER_SYSTEM.md) - Type-specific processing architecture

## Core Classes

### Service Layer

- [`CharsetHelper`](classes/CharsetHelper.md) - Static facade for backward compatibility
- [`CharsetProcessor`](classes/CharsetProcessor.md) - Main service implementation
- [`CharsetProcessorInterface`](classes/CharsetProcessorInterface.md) - Service contract

### Interfaces

- [`PrioritizedHandlerInterface`](classes/PrioritizedHandlerInterface.md) - Priority system contract

## Interpreter System

### Interfaces

- [`TypeInterpreterInterface`](classes/TypeInterpreterInterface.md) - Type interpreter contract
- [`PropertyMapperInterface`](classes/PropertyMapperInterface.md) - Custom property mapping contract

### Implementations

- [`InterpreterChain`](classes/InterpreterChain.md) - Chain of Responsibility coordinator
- [`StringInterpreter`](classes/StringInterpreter.md) - String processing (priority: 100)
- [`ArrayInterpreter`](classes/ArrayInterpreter.md) - Recursive array processing (priority: 50)
- [`ObjectInterpreter`](classes/ObjectInterpreter.md) - Object processing with property mapping (priority: 30)

## Transcoder System

### Interfaces

- [`TranscoderInterface`](classes/TranscoderInterface.md) - Transcoder contract

### Implementations

- [`TranscoderChain`](classes/TranscoderChain.md) - Chain of Responsibility coordinator
- [`UConverterTranscoder`](classes/UconverterTranscoder.md) - ext-intl conversion (priority: 100)
- [`IconvTranscoder`](classes/IconvTranscoder.md) - ext-iconv conversion (priority: 50)
- [`MbStringTranscoder`](classes/MbStringTranscoder.md) - ext-mbstring conversion (priority: 10)
- [`CallableTranscoder`](classes/CallableTranscoder.md) - Adapter for legacy callable transcoders

## Detector System

### Interfaces

- [`DetectorInterface`](classes/DetectorInterface.md) - Detector contract

### Implementations

- [`DetectorChain`](classes/DetectorChain.md) - Chain of Responsibility coordinator
- [`BomDetector`](classes/BomDetector.md) - BOM detection (priority: 160)
- [`PregMatchDetector`](classes/PregMatchDetector.md) - Fast ASCII/UTF-8 detection (priority: 150)
- [`MbStringDetector`](classes/MbStringDetector.md) - mb_detect_encoding (priority: 100)
- [`FileInfoDetector`](classes/FileInfoDetector.md) - finfo detection (priority: 50)
- [`CallableDetector`](classes/CallableDetector.md) - Adapter for legacy callable detectors
- [`CachedDetector`](classes/CachedDetector.md) - PSR-16 cache decorator

## Cache System

- [`InternalArrayCache`](classes/InternalArrayCache.md) - Optimized cache without TTL overhead
- [`ArrayCache`](classes/ArrayCache.md) - Full PSR-16 implementation with TTL support

## Cleaner System

### Interfaces

- [`CleanerInterface`](classes/CleanerInterface.md) - Cleaner contract
- [`CleanerStrategyInterface`](classes/CleanerStrategyInterface.md) - Execution strategy contract

### Chain

- [`CleanerChain`](classes/CleanerChain.md) - Chain of Responsibility coordinator

### Strategies

- [`PipelineStrategy`](classes/PipelineStrategy.md) - Apply all cleaners successively
- [`FirstMatchStrategy`](classes/FirstMatchStrategy.md) - Stop at first success
- [`TaggedStrategy`](classes/TaggedStrategy.md) - Selective execution by tags

### Default Cleaners

- [`MbScrubCleaner`](classes/MbScrubCleaner.md) - mb_scrub() for best quality (priority: 100)
- [`PregMatchCleaner`](classes/PregMatchCleaner.md) - Fastest, removes control chars (priority: 50)
- [`IconvCleaner`](classes/IconvCleaner.md) - Universal fallback with //IGNORE (priority: 10)

### Additional Cleaners

- [`BomCleaner`](classes/BomCleaner.md) - Removes BOM (priority: 150)
- [`NormalizerCleaner`](classes/NormalizerCleaner.md) - Normalizes Unicode (priority: 90)
- [`Utf8FixerCleaner`](classes/Utf8FixerCleaner.md) - Repairs UTF-8 corruption (priority: 80)
- [`HtmlEntityCleaner`](classes/HtmlEntityCleaner.md) - Decodes HTML entities (priority: 60)
- [`WhitespaceCleaner`](classes/WhitespaceCleaner.md) - Normalizes whitespace (priority: 40)
- [`TransliterationCleaner`](classes/TransliterationCleaner.md) - Transliterates to ASCII (priority: 30)

## Traits

- [`CallableAdapterTrait`](classes/CallableAdapterTrait.md) - Common logic for callable adapters
- [`ChainOfResponsibilityTrait`](classes/ChainOfResponsibilityTrait.md) - Generic CoR implementation

## Priority Reference

### Detectors (Highest to Lowest)

1. BomDetector: 160
2. PregMatchDetector: 150
3. MbStringDetector: 100
4. FileInfoDetector: 50

### Transcoders (Highest to Lowest)

1. UConverterTranscoder: 100
2. IconvTranscoder: 50
3. MbStringTranscoder: 10

### Interpreters (Highest to Lowest)

1. StringInterpreter: 100
2. ArrayInterpreter: 50
3. ObjectInterpreter: 30

### Cleaners (Highest to Lowest)

1. BomCleaner: 150
2. MbScrubCleaner: 100
3. NormalizerCleaner: 90
4. Utf8FixerCleaner: 80
5. HtmlEntityCleaner: 60
6. PregMatchCleaner: 50
7. WhitespaceCleaner: 40
8. TransliterationCleaner: 30
9. IconvCleaner: 10
