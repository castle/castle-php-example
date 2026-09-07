#!/usr/bin/env bash
# Point the example app at a specific Castle PHP SDK source.
#
#   set-sdk-version.sh main      -> track the SDK's main branch (pre-release testing)
#   set-sdk-version.sh 4.1.0     -> pin the released ^4.1 package from Packagist
#
# Rewrites the castle/castle-php constraint in composer.json.
set -euo pipefail

target="${1:?usage: set-sdk-version.sh <main|X.Y.Z>}"

python3 - "$target" <<'PY'
import json
import sys

target = sys.argv[1]
path = "composer.json"
with open(path) as f:
    data = json.load(f)

if target == "main":
    data["require"]["castle/castle-php"] = "dev-main"
else:
    major, minor = target.split(".")[:2]
    data["require"]["castle/castle-php"] = f"^{major}.{minor}"

with open(path, "w") as f:
    json.dump(data, f, indent=2)
    f.write("\n")
PY
