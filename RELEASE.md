# Release Process

This repo ships WordPress.org releases through GitHub Actions. This is the release pattern to copy into other plugin repos that use the same packaging setup.

## Release Contract

- `npm run dist` must build a runtime-only plugin directory at `dist/summaraize/`.
- `npm run dist` must also produce an installable zip at `dist/summaraize.zip`.
- `.github/workflows/plugin-check.yaml` validates the plugin on `push`, `pull_request`, and manual runs.
- The same workflow deploys to WordPress.org only when a GitHub Release is published.
- WordPress.org deploy uses `10up/action-wordpress-plugin-deploy` with:
  - `SLUG=summaraize`
  - `BUILD_DIR=dist/summaraize`
  - `ASSETS_DIR=assets`
- GitHub repo secrets required for deploy:
  - `WPORG_USERNAME`
  - `WPORG_PASSWORD`

## Standard Release Steps

1. Finalize the release commit on `main`.
   - Include version bumps, `readme.txt`, `README.md`, asset changes, and any workflow fixes before tagging.
2. Run the local release gate on that exact commit.
   - `npm run check`
   - `npm test`
   - `npm run dist`
3. Verify the build output shape.
   - `dist/summaraize/` must be the plugin root.
   - `dist/summaraize.zip` must install with a top-level `summaraize/` folder.
4. Push `main`.
   - The tagged commit and default branch should match.
5. Create and push an annotated version tag.
   - Example: `git tag -a v1.3.0 -m "Release v1.3.0"`
   - Example: `git push origin v1.3.0`
6. Publish the GitHub Release from that tag.
   - The release workflow will re-run validation, deploy to wp.org SVN, and attach the generated zip to the GitHub Release.
7. Verify the release outputs.
   - Confirm the GitHub Actions release run succeeded.
   - Confirm the GitHub Release includes the attached zip.
   - Confirm wp.org SVN deploy succeeded.
8. Check the public wp.org page last.
   - The directory page can lag behind a successful SVN deploy.

## Failure Recovery

- If the workflow is wrong, fix it on `main` first. Release workflows run from the tagged commit, not from the current default branch state.
- If the tag points at the wrong commit, move the tag to the corrected commit and recreate the GitHub Release so the correct workflow file runs.
- If GitHub Actions fails on Composer platform requirements, update the workflow PHP version to satisfy `composer.lock`, then retag.
- If deploy fails because of build structure, fix the `npm run dist` contract or `BUILD_DIR`. Do not add one-off unzip steps to compensate.
- If SVN deploy succeeds but wordpress.org still looks stale, treat that as propagation delay unless the workflow logs show an actual SVN failure.

## Notes From `v1.3.0`

- The successful release run was GitHub Actions run `24590008406`.
- The release worked after retagging `v1.3.0` to the commit that contained the corrected workflow.
- GitHub Actions emitted non-blocking Node 20 deprecation warnings for some marketplace actions. Those should be refreshed before GitHub enforces its Node 24 runtime change.
