# Schema Nerd

API interface for Schema Nerd organizations.

**Version:** 1.0.6

WordPress plugin readme for directory checks: see [readme.txt](readme.txt).

## Private GitHub updates

Save a GitHub personal access token under **Schema Nerd → Settings → Plugin updates**. The token needs **Contents: Read-only** access to `LocalImageBuilder/schema-nerd`.

Create a release by pushing a tag:

```bash
git tag v1.0.6
git push origin v1.0.6
```

GitHub Actions attaches `schema-nerd.zip` to the release. WordPress will detect the new version when the installed plugin is older than the release tag.
