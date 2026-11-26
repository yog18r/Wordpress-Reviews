#!/usr/bin/env python3
"""
Build the WordPress.org version of the plugin as a zip.

    python tools/build-wporg.py            -> build/if-reviews-wporg-<version>.zip

The WordPress.org directory serves its own updates (guideline 8), so this
build is the GitHub release minus the GitHub updater:

  - drops vendor/ (Plugin Update Checker) and includes/updater.php, and the
    lines in if-reviews.php that load it, set its URL and name it in the
    `Update URI` header;
  - drops the banner and icon images (they go in the directory's own
    `assets` folder in SVN, not in the plugin);
  - turns readme.txt into its WordPress.org form: blocks between
    <!-- github --> and <!-- /github --> are removed and the text inside
    <!-- wporg ... --> is kept.

Everything else, including the folder name (`if-reviews`), is identical, so
reviews and settings carry over between the two versions.
"""

import fnmatch
import pathlib
import re
import subprocess
import sys
import zipfile

ROOT = pathlib.Path(__file__).resolve().parent.parent
SLUG = 'if-reviews'

# On top of .distignore (which the GitHub release also uses).
WPORG_EXCLUDE = [
    '/vendor',
    '/tools',
    '/includes/updater.php',
    '/assets/banner-*',
    '/assets/icon-*',
    '/assets/*.svg',
]


def patterns():
    lines = (ROOT / '.distignore').read_text(encoding='utf-8').splitlines()
    pats = [l.strip() for l in lines if l.strip() and not l.strip().startswith('#')]
    return pats + WPORG_EXCLUDE


def excluded(path, pats):
    for pat in pats:
        if pat.startswith('/'):
            pat = pat[1:]
            # Anchored: the path itself or anything under it.
            if fnmatch.fnmatch(path, pat) or path.startswith(pat.rstrip('/') + '/'):
                return True
        elif fnmatch.fnmatch(pathlib.PurePosixPath(path).name, pat):
            return True
    return False


def main_file(text):
    out = []
    for line in text.splitlines(keepends=True):
        if re.match(r'\s*\*\s*Update URI:', line):
            continue
        if 'includes/updater.php' in line or "'IF_REVIEWS_GITHUB_URL'" in line:
            continue
        out.append(line)
    text = ''.join(out)
    for needle in ('updater.php', 'Update URI', 'IF_REVIEWS_GITHUB_URL'):
        if needle in text:
            sys.exit('if-reviews.php still mentions ' + needle)
    return text


def readme(text):
    text = re.sub(r'<!-- github -->\n?.*?<!-- /github -->\n?', '', text, flags=re.S)
    text = re.sub(r'<!-- wporg\n(.*?)\n-->', r'\1', text, flags=re.S)
    if '<!--' in text:
        sys.exit('readme.txt has an unmatched marker')
    return text


def main():
    files = subprocess.run(['git', 'ls-files'], cwd=ROOT, capture_output=True, text=True, check=True).stdout.split('\n')
    pats = patterns()
    keep = sorted(f for f in files if f and not excluded(f, pats))

    version = re.search(r"define\( 'IF_REVIEWS_VERSION', '([^']+)' \);", (ROOT / 'if-reviews.php').read_text(encoding='utf-8')).group(1)
    out_dir = ROOT / 'build'
    out_dir.mkdir(exist_ok=True)
    out = out_dir / '{}-wporg-{}.zip'.format(SLUG, version)

    with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
        for f in keep:
            src = ROOT / f
            if f == 'if-reviews.php':
                z.writestr(SLUG + '/' + f, main_file(src.read_text(encoding='utf-8')))
            elif f == 'readme.txt':
                z.writestr(SLUG + '/' + f, readme(src.read_text(encoding='utf-8')))
            else:
                z.write(src, SLUG + '/' + f)

    print(out)
    print('{} files'.format(len(keep)))


if __name__ == '__main__':
    main()
