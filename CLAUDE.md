# Fairytale Project

Standards: inherits ../engineering-standards.md. Overrides & project-specifics below.

**Stack:** vanilla-JS — hand-authored static HTML/CSS/JS, no framework, no build step (§3 applies)

## Commands
- dev: `python3 -m http.server 8000` from the project root, then open http://localhost:8000 (or just open `index.html`)
- build: n/a (hand-authored static site — no build pipeline)
- test / check: —

## Commit protocol
Ask before committing (see ../CLAUDE.md). Note: `fairytale-deploy.zip` is a ~1.3GB artifact and the `images/` dir holds ~4,900 files — keep large media/zip out of commits.

## Version tag
Source: none  •  Shown in UI: nowhere — none yet — add a version constant + visible tag per ../CLAUDE.md when it gains a build/UI

## Project-specific rules & overrides
- Static multilingual site (Chinese / English / German — see the `童话项目 | Fairytale Project | Fairytale-Projekt` HTML files).
- Deployed by FTP upload, not git push. FTP credentials live in `admin creds for FTP.txt` (do NOT commit). A prebuilt `fairytale-deploy.zip` is the deploy bundle.
- Content is data-driven: `fairytale-content.json` (~10MB) and `fairytale-data.json` feed the pages; PHP exporters under `scripts/` (`export-*.php`, `exportBlogToJson-with-images.php`) regenerate the JSON. The PHP is tooling, not the runtime stack.
- No package.json, no framework, no test harness — edit HTML/JS directly.
