
#!/bin/bash
set -e
if [ -z "$1" ]; then
  echo "Usage: ./prepare_repo.sh <your-github-username-or-org> (example: ./prepare_repo.sh yurileal11)"
  exit 1
fi
REPO_NAME=phpsecureform-ai
GIT_REMOTE="https://github.com/$1/$REPO_NAME.git"
git init
git add .
git commit -m "Initial PoC phases 0-4"
git branch -M main
git remote add origin $GIT_REMOTE
echo "Remote set to $GIT_REMOTE. Push with: git push -u origin main"
