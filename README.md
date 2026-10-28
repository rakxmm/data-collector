# Data Collector

A web app for timing customers in a self-service restaurant by hand:
arrival → choosing food → queue → service → … → departure.

Plain PHP 8.3, SQLite, no framework. Slovak and English.

## Run

```bash
docker compose up -d --build
```

Open <http://localhost:8080>, or `http://<computer IP>:8080` from a phone on the same Wi-Fi.

To require a login, set `BASIC_AUTH`:

```bash
BASIC_AUTH=user:password docker compose up -d
```

Data is stored in the `data-collector-data` Docker volume.

## Usage

- **Customer arrived** (or the `N` key) adds a customer and starts the first phase.
- **Next** starts the next phase. After the last phase it records the departure.
- **Skip** – the customer left out the next phase.
- **Undo** takes back the last step.
- Phases and yes/no questions are edited in **Settings**.
- **Records** shows average times and exports CSV (one row per customer, or one row per phase).

## Code

```
public/   pages (index, records, settings, export) and form handlers (customer, preferences)
src/      logic: database, steps, customers, export, helpers, translations
views/    HTML templates
lang/     translations
```

Texts are translated with `_t('Key', 'Slovak text')`, English is in `lang/en.php`.
