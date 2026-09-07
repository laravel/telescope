---
name: telescope-debugging
description: Inspect what a Laravel app actually did using the telescope:list and telescope:show artisan commands. Use when debugging a slow page, an exception or 500 response, a failing queued job, an N+1 or slow query, or unexpected cache misses in a project that has laravel/telescope installed, even when Telescope is not mentioned.
---

Telescope records every request, job, and command as a **batch**: the entry itself plus every query, cache operation, log, event, exception, and view it produced. The two commands below read that store from the terminal. Prefer them over the web UI.

### Workflow

1. **Find the entry.** Run `php artisan telescope:list request` (or `exception`, `job`, `query`, `cache`). Pick the row by URI, class, or duration and copy its UUID. Skip this step when the most recent entry is the one you want.

2. **Show the batch.** Run `php artisan telescope:show <uuid>`, or `php artisan telescope:show latest:request`. The output is the entry's own detail followed by Queries, Exceptions, Cache, and Logs sections, all from the same batch.

3. **Diagnose from the flags.** In the Queries table, `DUP` marks queries that share one SQL pattern, which usually indicates an N+1, and the Source column names the file and line that ran them. `SLOW` marks queries over the configured threshold. The Cache header states hits, misses, and hit rate. An exception entry shows the message, code context, and stack trace.

4. **Done** when the finding names a file and line or a specific query, not a symptom.

### JSON output

Both commands accept `--json`. Prefer it over the tables when you need exact values, want to filter, or the table output would be long. `telescope:list --json` prints an array of entries. `telescope:show --json` prints `{"entry": {...}, "batch": [...]}`, with the batch in chronological order and already filtered by `--type`. Every entry has `id`, `sequence`, `batch_id`, `type`, `content`, `tags`, `family_hash`, and `created_at`. The `content` keys match what the Telescope UI shows for that type, for example `sql`, `time`, `slow`, `file`, `line` on a query, or `class`, `message`, `file`, `line`, `trace` on an exception.

```bash
# Exception class, message, and location for the latest exception
php artisan telescope:show latest:exception --json | jq '.entry.content | {class, message, file, line}'

# Slow queries in the latest request, with the file that ran them
php artisan telescope:show latest:request --json --type=query | jq '.batch[] | select(.content.slow) | {sql: .content.sql, time: .content.time, file: .content.file, line: .content.line}'

# Repeated SQL patterns in a request (N+1 candidates), most repeated first
php artisan telescope:show latest:request --json --type=query | jq '[.batch[].content.sql] | group_by(.) | map({sql: .[0], count: length}) | sort_by(-.count) | .[] | select(.count > 1)'

# Recent 500 responses
php artisan telescope:list request --json --limit=50 | jq '.[] | select(.content.response_status >= 500) | {id, uri: .content.uri, status: .content.response_status}'

# Failed jobs and their exception messages
php artisan telescope:list job --json | jq '.[] | select(.content.status == "failed") | {id, name: .content.name, error: .content.exception.message}'
```

### Reference

- `latest`, `latest:request`, `latest:exception`, and `latest:job` resolve to the most recent entry of that type, so no UUID lookup is needed.
- `--json` on either command skips truncation entirely, so `--full` is only needed for table output.
- `telescope:show <id> --full` disables truncation of SQL, messages, and payloads.
- `telescope:show <id> --type=query,exception` limits the batch context to those types when a request produced hundreds of entries.
- `telescope:list --tag=Auth:42` filters to one authenticated user, since Telescope tags entries with `Auth:<user id>`. `--batch=<id>` lists everything from one request. `--before=<sequence>` pages backwards using the cursor printed in the footer.
- Run either command with `--help` for the full option list and valid entry types.
