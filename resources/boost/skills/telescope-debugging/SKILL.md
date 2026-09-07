---
name: telescope-debugging
description: Inspect what a Laravel app actually did using the telescope:list and telescope:show artisan commands. Use when debugging a slow page, an exception or 500 response, a failing queued job, an N+1 or slow query, or unexpected cache misses in a project that has laravel/telescope installed, even when Telescope is not mentioned.
---

Telescope records every request, job, and command as a **batch**: the entry itself plus every query, cache operation, log, event, exception, and view it produced. The two commands below read that store from the terminal. Prefer them over the web UI.

### Workflow

1. **Find the entry.** Run `php artisan telescope:list request` (or `exception`, `job`, `query`, `cache`). Pick the row by URI, class, or duration and copy its UUID. Skip this step when the most recent entry is the one you want.

2. **Show the batch.** Run `php artisan telescope:show <uuid>`, or `php artisan telescope:show latest:request`. The output is the entry's own detail followed by Queries, Exceptions, Cache, and Logs sections, all from the same batch.

3. **Diagnose from the flags.** In the Queries table, `DUP` marks queries that share one SQL pattern, which is an N+1, and the Source column names the file and line that ran them. `SLOW` marks queries over the configured threshold. The Cache header states hits, misses, and hit rate. An exception entry shows the message, code context, and stack trace.

4. **Done** when the finding names a file and line or a specific query, not a symptom.

### Reference

- `latest`, `latest:request`, `latest:exception`, and `latest:job` resolve to the most recent entry of that type, so no UUID lookup is needed.
- `telescope:show <id> --type=query,exception` limits the batch context to those types when a request produced hundreds of entries.
- `telescope:list --tag=Auth:42` filters to one authenticated user, since Telescope tags entries with `Auth:<user id>`. `--batch=<id>` lists everything from one request. `--before=<sequence>` pages backwards using the cursor printed in the footer.
- Run either command with `--help` for the full option list and valid entry types.
