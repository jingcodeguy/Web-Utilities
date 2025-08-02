## [Released]

## 2025-08-02
- Added README.md to explain usage of the SiteGround cache purge tool
- Added LICENSE for legal clarity
- Refactored `purge_cache.php`:
  - Removed WordPress-specific logic
  - Now works as a standalone PHP script for SiteGround hosting
  - Ready for direct use via browser or `curl` in CLI