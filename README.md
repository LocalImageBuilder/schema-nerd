# Schema Nerd

API interface for Schema Nerd organizations.

**Version:** 1.2.0

WordPress plugin readme: see [readme.txt](readme.txt).

## Automatic updates (GitHub)

Updates come from **public** GitHub Releases on `LocalImageBuilder/schema-nerd`.

1. Make the repository **public** (private repos require a token; this plugin does not use one).
2. Tag a release:

```bash
git tag v1.2.0
git push origin v1.2.0
```

3. GitHub Actions attaches `schema-nerd.zip` to the release.
4. WordPress sites with the plugin installed will see the update under **Dashboard → Updates**.

No GitHub token is stored in the plugin or site settings.
