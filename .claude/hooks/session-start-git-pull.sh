#!/usr/bin/env bash
# SessionStart hook: git pull + видимое сообщение пользователю о результате.
set -uo pipefail

pull_output=$(git pull 2>&1)
pull_status=$?
last_commit=$(git log -1 --format='%h %s (%ci)' 2>&1)

if [ $pull_status -eq 0 ]; then
  if echo "$pull_output" | grep -q "Already up to date"; then
    prefix="Git pull: актуальная версия, обновлений нет."
  else
    prefix="Git pull: получены обновления."
  fi
else
  prefix="Git pull: ОШИБКА."
fi

message="${prefix}
Текущий коммит: ${last_commit}
---
${pull_output}"

python -c "
import json, sys
print(json.dumps({'systemMessage': sys.argv[1]}))
" "$message"
