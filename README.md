# provider-luftdaten

Luftdaten-Provider for [luft.jetzt](https://luft.jetzt). Fetches air quality data (PM10, PM2.5) from the [sensor.community](https://sensor.community/) API and pushes it to the luft.jetzt API.

## Requirements

- PHP 8.1+
- Composer
- ext-ctype
- ext-iconv

## Installation

```bash
composer install
```

Copy `.env` to `.env.local` and configure your environment variables:

```bash
cp .env .env.local
```

## Commands

### `luft:fetch`

Fetches current measurement data from sensor.community and sends it to the luft.jetzt API.

```bash
php bin/console luft:fetch
php bin/console luft:fetch -v   # verbose output with value table
```

### `luft:archive`

Processes historical CSV archive data for a given time range.

```bash
php bin/console luft:archive <from-date-time> <until-date-time> [options]
```

| Option | Description |
|---|---|
| `--tag` | Tag to assign to all values |
| `--pollutant` | Filter by pollutant type |

Example:

```bash
php bin/console luft:archive "2024-01-01" "2024-01-31" --pollutant=pm10 --tag=january
```

## Configuration

Environment variables are managed via `.env` files (Symfony Dotenv):

| Variable | Description |
|---|---|
| `APP_ENV` | Application environment (`dev`, `prod`) |
| `APP_SECRET` | Symfony application secret |

## License

Proprietary
