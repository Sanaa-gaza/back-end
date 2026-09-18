---
name: git-workflow
description: Branching, commit message, and pull request rules for the Senaa project. Use whenever committing, creating a branch, or preparing a pull request.
---

# Git Workflow — Senaa

Branching, commit, and pull request conventions for the Senaa project.

## Branches

- `main` — stable only. Never push directly to `main`.
- `develop` — integration branch. All feature/fix branches merge here.
- `feature/<short-name>` — for any new piece of work.
- `fix/<short-name>` — for a bug fix.

Every branch is created from `develop`, never from `main`.

## Commit messages

- Written in English, imperative mood: `Add customer registration endpoint`.
- First line no longer than 70 characters.
- One commit per logical change. Don't bundle five unrelated tasks into a single commit.

## Before opening a Pull Request

Check all of the following:

- All tests pass.
- No temporary files or secrets are included in the diff.
- `.env` was never added.
- The Postman collection is updated if any endpoint changed (see `postman-docs`).
- The code follows the rules in the `senaa-sprint1-backend` skill.

## Pull Request description

Must state:

- What changed.
- Why it changed.
- How to test it.

## Review

Never merge your own Pull Request without review from another team member.
