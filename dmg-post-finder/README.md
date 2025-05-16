# DMG Post Finder

A WordPress plugin to find posts via WP-CLI or an editor block, and display them as a styled link.

## Features

### Gutenberg Block

This plugin provides a Gutenberg block that allows editors to:
- Search for published posts by title or ID
- Choose from paginated search results
- View and select from recent posts
- Insert a stylized "Read More" link to the selected post

### WP-CLI Command

The plugin also adds a CLI command for finding posts that contain the DMG Post Finder block:

```bash
# Search for posts with the block in the last 30 days (default)
wp dmg-read-more search

# Search for posts with the block within a specific date range
wp dmg-read-more search --date-after=2023-01-01 --date-before=2023-12-31
```

## Usage

### Using the Block

1. Add the "DMG Post Finder" block to your post or page
2. In the block sidebar, search for posts by title or ID
3. Select a post from search results or recent posts
4. The block will display as a styled link with "Read More: [Post Title]"

### Building the Block

For development, you'll need to build the JavaScript assets:

```bash
# Navigate to plugin directory
cd wp-content/plugins/dmg-post-finder

# Install dependencies
npm install

# Build assets
npm run build

# For development with automatic rebuilding
npm run start
``` 