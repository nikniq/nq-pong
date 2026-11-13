#!/usr/bin/env bash
# Stop hook: commit everything and push to origin after each Claude turn.
cd "$CLAUDE_PROJECT_DIR" 2>/dev/null || cd "$(dirname "$0")/../.."
git add -A
if ! git diff --cached --quiet; then
  FILES=$(git diff --cached --name-only | head -5 | paste -sd', ' -)
  git commit -q -m "Auto: update ${FILES}" -m "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
fi
BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ -n "$(git log --oneline "origin/$BRANCH..HEAD" 2>/dev/null || echo x)" ]; then
  if git push -q -u origin "$BRANCH" 2>/tmp/autopush.err; then
    echo "{\"systemMessage\": \"Auto-pushed $BRANCH to GitHub\"}"
  else
    echo "{\"systemMessage\": \"Auto-push failed: $(head -1 /tmp/autopush.err | tr -d '\"')\"}"
  fi
fi
exit 0
