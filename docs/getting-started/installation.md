# Installation

## Requirements

- **PHP**: 7.4, 8.0, 8.1, 8.2, or 8.3
- **Extensions** (required):
  - `ext-mbstring`
  - `ext-json`
- **Extensions** (recommended):
  - `ext-intl` (30% performance boost)
  - `ext-iconv` (transliteration support)
  - `ext-fileinfo` (advanced detection)

## Via Composer

```bash
composer require ducks-project/encoding-repair
```

## Optional Extensions

### Ubuntu/Debian

```bash
sudo apt-get install php-intl php-iconv
```

### macOS (via Homebrew)

```bash
brew install php@8.2
# Extensions are included by default
```

### Windows

Enable in `php.ini`:

```ini
extension=intl
extension=iconv
```

## Verify Installation

```bash
php -v                     # Check PHP version (>= 7.4)
php -m | grep mbstring     # Verify mbstring
php -m | grep json         # Verify json
php -m | grep intl         # Check intl (optional)
```

## Next Steps

- [Quick Start Guide](quick-start.md)
- [Basic Usage](../guide/basic-usage.md)
