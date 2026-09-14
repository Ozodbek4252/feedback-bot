# Feedback Bot — Handover

What this is: a Laravel app that receives Telegram messages sent to per-app
"feedback bots" and forwards them into private Telegram groups, with admins
able to reply from the group back to the original user.

Infra specifics (server address, SSH key, deploy paths) live in
`HANDOVER.local.md`, which is gitignored and never leaves this machine —
copy it by hand to any other machine you work from.

## CI/CD

`.github/workflows/deploy.yml` — on every push to `master`, GitHub Actions
SSHes into the production server and runs:

```
git fetch origin master && git reset --hard origin/master
docker compose build app
docker compose up -d --remove-orphans
```

Required repo secrets (GitHub → Settings → Secrets and variables → Actions):

- `DEPLOY_HOST`
- `DEPLOY_USER`
- `DEPLOY_SSH_KEY`

(Values are in `HANDOVER.local.md`.)

## Local dev

```
docker compose up --build
```

- App: http://localhost:8080
- Adminer: http://localhost:8081
- `.env` is gitignored — copy `.env.example` and fill in real values (see
  "Secrets" below). Without `TELEGRAM_SHELF_BOT_TOKEN`/`_CHAT_ID` set, the
  poller service will just log an error and exit.

**Important**: Telegram only allows one consumer of `getUpdates` per bot
token at a time. Don't run a local poller service at the same time as the
one on the production server — stop one before starting the other, or
you'll get 409 Conflict errors.

## Architecture

- Laravel 13, PHP 8.4, Postgres 17. Single Docker image runs nginx + php-fpm
  together via supervisord (see `Dockerfile`, `docker/`).
- `config/telegram.php` — one entry per app/bot (slug → token/chat_id), driven
  entirely by `.env`.
- `app/Console/Commands/TelegramPollCommand.php` — `php artisan telegram:poll
  {slug}` long-polls Telegram's `getUpdates` for one bot. Runs forever as its
  own docker-compose service (e.g. `telegram-poller-shelf`).
- `app/Services/Telegram/TelegramApi.php` — thin wrapper around the Telegram
  Bot API (`getUpdates` / `sendMessage` / `forwardMessage` / `copyMessage`).
- `app/Services/Telegram/FeedbackForwarder.php` — the actual logic:
  - Message from a real user (private chat with the bot) → stored in
    `feedback_messages`, then forwarded into the configured group via
    `forwardMessage`.
  - Reply **inside the group** to one of the bot's forwarded messages →
    relayed back into that user's private chat via `copyMessage`. This is
    how an admin responds to feedback — just hit Reply in the group.
  - Anything else from the group is ignored (not stored, not echoed).
- `telegram_poll_states` table tracks each bot's last processed Telegram
  `update_id` so restarts don't reprocess or miss messages.

### Why long-polling, not webhooks

Telegram webhooks require HTTPS on port 443/80/88/8443 with a valid
certificate. No domain is pointed at the production server yet, so webhooks
aren't possible right now. Long-polling needs zero public infrastructure and
is already running. Switching to a webhook later doesn't require touching
`FeedbackForwarder` — just swap the entry point (a controller route instead
of the poll command).

## Bots configured today

| Slug    | Bot                  | App   | Target group        |
|---------|----------------------|-------|----------------------|
| `shelf` | @shelf_feedback_bot  | Shelf | "Shelf — Feedback"   |

## Adding a new app (e.g. Cyclo)

1. Create the bot with @BotFather, get its token.
2. Create/choose the target group, add the bot as admin, get the group's
   chat_id (send any message in the group, then check
   `https://api.telegram.org/bot<TOKEN>/getUpdates`, or temporarily add
   @userinfobot to the group).
3. Add to `.env` — **both locally and on the server** (`.env` is gitignored,
   so this has to be edited by hand in both places):
   ```
   TELEGRAM_CYCLO_BOT_TOKEN=...
   TELEGRAM_CYCLO_BOT_CHAT_ID=...
   ```
4. Uncomment/fill in the `cyclo` block in `config/telegram.php`.
5. Copy the `telegram-poller-shelf` service block in `docker-compose.yml`,
   rename it to `telegram-poller-cyclo`, change the command to
   `["php", "artisan", "telegram:poll", "cyclo"]`.
6. Commit + push to `master` — CI deploys automatically. Remember step 3's
   server-side `.env` edit isn't part of the deploy; do it via SSH.

## Secrets — where they actually live (never committed to git)

- Local: `.env` in the project root (gitignored).
- Server: the app's `.env` on the production host (root-owned, `chmod 600`).
- GitHub Actions: repo secrets (see CI/CD above).
- Currently set: DB password, `APP_KEY`, and the Shelf bot token/chat_id —
  all generated or provided during setup. Not reproduced here since this
  repo is public. See `HANDOVER.local.md`.

## Known non-issues

- One early test message (id 1, text "a") in `feedback_messages` never got
  `forwarded_at` set — Telegram no longer had that message to forward
  (stale/deleted before polling started). Logged, not a bug.
