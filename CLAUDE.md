# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP library for merging page assets compiled by the Brizy compiler. It aggregates, optimizes, and sorts CSS/JS assets and fonts from multiple asset groups, with intelligent deduplication and font grouping.

## Key Architecture Components

### Core Classes
- **AssetAggregator** (`lib/AssetAggregator.php`): Main orchestrator that processes asset groups and returns optimized asset lists
- **AssetGroup** (`lib/Assets/AssetGroup.php`): Container for related assets including main, generic, libraries, selectors, fonts, and styles
- **Asset** (`lib/Assets/Asset.php`): Base asset class with support for inline content, file URLs, and pro/free variants
- **AssetLib** (`lib/Assets/AssetLib.php`): Library assets with CSS selectors for conditional inclusion
- **AssetFont** (`lib/Assets/AssetFont.php`): Font assets with special handling for Google Fonts and uploaded fonts

### Key Processing Logic
The AssetAggregator performs several optimization steps:
1. **Asset Aggregation**: Collects assets from multiple AssetGroups, prioritizing pro versions
2. **Library Optimization**: Groups smaller libraries into larger ones when possible to reduce HTTP requests
3. **Font Grouping**: Combines Google Fonts and uploaded fonts with the same family into single requests
4. **Version Management**: Uses latest version of asset groups for library maps
5. **Deduplication**: Removes duplicate assets
6. **Sorting**: Orders assets by score for optimal loading

### Font Type Constants
- `FONT_TYPE_GOOGLE`: Google Fonts (uses `?family=` parameter)
- `FONT_TYPE_UPLOADED`: Uploaded fonts (uses `-font=` parameter)

## Development Commands

### Dependencies
```bash
composer install
```

### Running Tests
```bash
./vendor/bin/phpunit
```

Run specific test:
```bash
./vendor/bin/phpunit tests/AssetAggregatorTest.php
```

### Project Structure
- `lib/`: Main library code with PSR-4 autoloading (`BrizyMerge\` namespace)
- `tests/`: PHPUnit tests with PSR-4 autoloading (`BrizyMergeTests\` namespace)
- `vendor/`: Composer dependencies
- `phpunit.xml.dist`: PHPUnit configuration

## Implementation Notes

### Asset Creation Patterns
- Use `Asset::instanceFromJsonData()` when creating assets from compiler output
- Use `AssetGroup::instanceFromJsonData()` for full asset groups from JSON
- Assets have a `score` property for load order optimization (lower scores load first)

### Pro vs Free Assets
The system distinguishes between pro and free assets, with pro assets taking precedence when available. This affects library selection and main asset choice.

### Library Selection Algorithm
Libraries are selected based on CSS selectors. The system tries to find the smallest library that contains all required selectors, then attempts to group multiple small libraries into larger ones to optimize HTTP requests.