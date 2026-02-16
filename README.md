
# PHPSecureForm AI

Project: An ethical scanner for file uploads in PHP applications (PHP 8.4).
This repository is prepared for: https://github.com/yurileal11 (add remote and push after reviewing).

Goal: Intercept HTTP requests/responses (Burp integration), forward them to a local analysis service that uses AI (OpenAI) and automated heuristics to identify problematic upload handling, CSRF, WAF indications, and suggest controlled tests up to potential RCE vectors — strictly for authorized testing in lab environments.

**IMPORTANT WARNING (READ BEFORE RUNNING)**
- Sensitive data may be forwarded to the OpenAI API when enabled. Do **not** send secrets or personal data in production or without consent.
- This is a proof-of-concept. By default, active replay/testing is disabled. Enable it explicitly via `.env` when you understand the risks and are in a safe lab environment.

## What is included
- Project scaffold with Docker-compose for PHP 8.4 + Nginx frontend.
- `/api/intercept` endpoint that receives JSON with request+response, stores it, runs local heuristics (Analyzer, WAFDetector, Mutator) and optionally enriches with OpenAI if an API key is set.
- `/api/replay` endpoint to execute **controlled** test mutations against the original target **only if** `ALLOW_REPLAY=true` in `.env` (disabled by default).
- More advanced modules:
  - `Analyzer.php` — parameter extraction, multipart parsing, CSRF token detection heuristics.
  - `Mutator.php` — safe mutation generation for parameters and file uploads (non-exploitative fuzzing).
  - `WAFDetector.php` — simple heuristics to detect signs of WAF/protection in responses.
  - `OpenAIClient.php` — wrapper to call OpenAI for enrichment (English prompts).
- Burp extension skeleton for forwarding intercepted traffic to the local PoC service.
- Instructions to compile/load the Burp extension, and how to run the system in Docker.

## UX & Burp controls
- Web UI: `public/dashboard.php` to list intercepted requests, view details and summaries, and trigger safe replays (replay is allowed only when `ALLOW_REPLAY=true`).
- Control API: `api/control.php` exposes a small API so extensions can check whether the PoC is accepting intercepts and whether automated replay is allowed. The Burp extension skeleton includes a polling example for that endpoint and a Start/Stop button implementation comment.
- Extension behavior: the Burp extension must forward intercepted traffic to `/api/intercept`. The extension should also periodically poll `/api/control` to respect the Start/Stop toggle and not forward traffic if disabled.
- Repo setup: included helper script `prepare_repo.sh` to initialize git, commit, and set remote to your GitHub: `https://github.com/yurileal11/phpsecureform-ai.git` (you must run and push with your credentials).

## How to run (quick)
1. Copy `.env.example` -> `.env` and set values. Make sure `BURP_CALLBACK_HOST` matches your environment.
2. Build and run:
   ```bash
   docker compose up --build -d
   ```
3. Open Dashboard: http://localhost:8000/dashboard.php
4. Load Burp extension (compile Java skeleton) and configure callback to `http://<BURP_CALLBACK_HOST>:8000/api/intercept`. The extension will respect `/api/control` toggles.
5. To enable replaying of tests, set `ALLOW_REPLAY=true` in `.env` and restart containers. Use the Dashboard to trigger replays (enter token when prompted).

## Security & Notes
- Replay of file uploads is intentionally manual to avoid dangerous automated uploads.
- The Analyzer and Mutator are for research and should be audited before real use.
