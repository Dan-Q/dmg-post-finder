# DMG Post Finder

A demonstration WordPress plugin to provide the following features:

## Features

### A Gutenberg Block

Write a Gutenberg block using native WP React tools (no ACF or other plugin dependencies). This block should allow editors to search for and
then choose a published post to insert into the editor as a stylized anchor link.

Editors should be able to search posts in the InspectorControls using a search string. It should paginate results. It should support searching
for a specific post ID. Recent posts should be shown to choose from by default.

The anchor text should be the post title and the anchor href should be the post permalink. The anchor should be output within a paragraph
element with a CSS class of `dmg-read-more` added to it. The anchor should be prepended with the words `Read More: `. Choosing a new post
should update the anchor link shown in the editor.

### A WP-CLI Command

Create a custom WP-CLI command named like, `dmg-read-more search`

This command will take optional date-range arguments like “date-before” and “date-after” If the dates are omitted, the command will default to
the last 30 days.

The command will execute a WP_Query search for Posts within the date range looking for posts containing the aforementioned Gutenberg block.
Performance is key, this WP-CLI command will be tested against a database that has tens of millions records in the wp_posts table.

The command will log to STDOUT all Post IDs for the matching results.

If no posts are found, or any other errors encountered, output a log message.

## Usage

### Docker-based development environment

This plugin is provided "batteries included" with a working WordPress development environment, configured using Docker. Get started by
running:

```bash
docker compose up
```

The following services will be exposed:

- http://localhost:8080/ (the test site)
- MariaDB server on port 8081 (username `root`, password `password`, database name `wp`)

The database will be preconfigured with a selection of sample posts for testing. The titles of these posts contain a variety of pet-related
words, e.g. "dog", "cat", "hamster".

### Working without Docker

The plugin lives in `dmg-post-finder/`. You can rebuild the JavaScript from sources with:

```bash
cd dmg-post-finder
npm install # if you haven't installed the dependencies
npm run build # or npm run start for auto watch
```

Linting is enabled with `npm run lint:js` and `npm run lint:php` (or `npm run lint` for both). PHP linting requires dependencies managed
using Composer (run `composer install` in the `dmg-post-finder` directory).

## Repository Layout

- `dmg-post-finder/` - ⭐ the plugin itself
    - `build/` - compiled version of the JavaScript code
    - `src/` - JSX sources
    - `includes/` - PHP code to support the plugin
    - `dmg-post-finder.php` - PHP entrypoint for the plugin
    - Composer dependency files (for WordPress Coding Standards linting)
    - Node dependency files
    - ESLint configuration
- `wp/` - WordPress gets installed here
    - `wp/wp-content/debug.log` - the Dockerised WordPress is configured with `WP_DEBUG=true`, logging to this file
- `wp-data/initial-db.sql` - skeleton data used to populate the dev database
- `docker-compose.yml` - Docker configuration
- `README.md` - this readme
