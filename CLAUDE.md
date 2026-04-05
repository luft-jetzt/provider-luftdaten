# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Symfony console application that fetches particulate matter data (PM2.5, PM10) from the Luftdaten.info / sensor.community API and historical CSV archives. Parses JSON and CSV formats and pushes measurements to the Luft.jetzt API.

- **PHP**: ^8.5
- **Framework**: Symfony 8.0
- **Data Source**: Luftdaten API (`api.luftdaten.info`) + CSV archives
- **Pollutants**: PM2.5, PM10

## Common Commands

```bash
composer install                                    # Install dependencies
php bin/console luft:fetch                          # Fetch current data from Luftdaten API
php bin/console luft:archive --from=2024-01-01 --until=2024-01-31  # Load archive data

vendor/bin/phpunit                                  # Run tests
vendor/bin/phpstan analyse --no-progress            # Static analysis
vendor/bin/php-cs-fixer fix                         # Code style fixing
```

## Architecture

### Live Data

1. **`SourceFetcher/SourceFetcher`** — Fetches JSON from `https://api.luftdaten.info/static/v2/data.dust.min.json`
2. **`Parser/JsonParser`** — Parses JSON response, extracts station ID, timestamp, pollutant values
3. Values are batched (1000 per batch) and pushed via `luft-api-bundle`

### Archive Data

1. **`ArchiveFetcher/ArchiveFetcher`** — Loads historical CSV data with date range filtering
2. **`ArchiveFetcher/ArchiveDataLoader`** — Locates archive files by date (from `/volume1/Luftdaten-Archiv/archive.sensor.community`)
3. **`Parser/CsvParser`** — Parses CSV archive files using `league/csv`

### Key Files

- `src/SourceFetcher/SourceFetcher.php` — Live data fetching
- `src/ArchiveFetcher/ArchiveFetcher.php` — Archive data loading
- `src/Parser/JsonParser.php` — JSON response parsing
- `src/Parser/CsvParser.php` — CSV archive parsing
- `src/Command/` — Console commands

## Dependencies

- `league/csv` ^9.28 — CSV parsing for archive data
- `symfony/http-client` ^8.0 — HTTP requests
- `luft-jetzt/luft-api-bundle` — Pushes data to Luft.jetzt API
