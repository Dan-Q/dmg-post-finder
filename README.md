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

### Using the Gutenberg Block

https://github.com/user-attachments/assets/1c7a159a-6413-4a47-8471-c3fb18eec173

#### Docker-based development environment

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

#### Working without Docker

The plugin lives in `dmg-post-finder/`. You can rebuild the JavaScript from sources with:

```bash
cd dmg-post-finder
npm install # if you haven't installed the dependencies
npm run build # or npm run start for auto watch
```

Linting is enabled with `npm run lint:js` and `npm run lint:php` (or `npm run lint` for both). PHP linting requires dependencies managed
using Composer (run `composer install` in the `dmg-post-finder` directory).

Mount the plugin into your favourite WordPress site and you're set to go.

### Using the WP-CLI Command

If you're using the Dockerised development environment, you can try out the search tool using a one-liner like this:

```bash
docker run -it --rm --volumes-from dmg-wp --network container:dmg-wp \
           -e WORDPRESS_DB_HOST=db -e WORDPRESS_DB_NAME=wp \
           -e WORDPRESS_DB_USER=root -e WORDPRESS_DB_PASSWORD=password \
           wordpress:cli \
           wp dmg-read-more search --date-after=2000-01-01 --date-before=2025-06-01 # <- this is the WP-CLI command to run
```

#### Performance Testing

To do some elementary performance testing, it might be useful to have a _lot_ of posts. You might happen to have tens of millions
of rows to-hand already, but if not, take a look at `20000-posts-for-testing.sql.gz`. Import that SQL file into your database to
introduce 200,000 Posts to your `wp_posts` table. Half of these new Posts have the new block on them and half do not. Ordered
by insert order they're in batches of 10,000 "with", 10,000 "without"; ordered chronologically they alternate.

These Posts span the period 2000-01-01 through 2000-01-11. So after importing them, you might perform a speed test with e.g.:

```bash
time \
docker run -it --rm --volumes-from dmg-wp --network container:dmg-wp \
           -e WORDPRESS_DB_HOST=db -e WORDPRESS_DB_NAME=wp \
           -e WORDPRESS_DB_USER=root -e WORDPRESS_DB_PASSWORD=password \
           wordpress:cli \
           wp dmg-read-more search --date-after=2000-01-01
```

Note that this will of course suffer from overheads as a result of spinning up the Docker container; you're likely to want to
run from within an existing container or else in a more-realistic environment if you're concerned about absolute performance metrics.

https://github.com/user-attachments/assets/9c271d2c-aaa3-49c3-842e-ab25ca5588e3

#### Alternative Implementations

Because of the stipulation that "performance is key", I've optimised for performance in the WP-CLI command at the expense of almost
everything else: this implementation uses an SQL `LIKE` clause to offload the processing onto the database server, which in almost
every case will result in better performance than pulling Posts in WordPress and checking with e.g. `has_block(...)`.

However, the approach has limitations that could result in false positives in the (highly-unlikely) event than an editor introduces
a _false_ HTML comment to their content. For a discussion of the implications and a demonstrative alternate implementation, see the
comments in `includes/class-cli.php`.

There of course exist further alternative implementations which could result in even-better performance, but it seemed unlikely that
this would actually be needed. For example, upon updating a Post a transient could be updated to add or remove the current Post's ID,
depending on whether or not it contained the new block. At the expense of slightly slower writes (when saving Posts), this would
maximise performance for the new WP-CLI command by reducing it to an O(1) problem (and, depending on the server configuration,
potentially load the data from RAM without hitting the database server at all).

## Repository Layout

- `dmg-post-finder/` - ⭐ the plugin itself
    - `build/` - compiled version of the JavaScript code
    - `src/` - JSX sources
    - `includes/`
        - `class-cli.php` - features relating to the new WP-CLI command
        - `class-gutenbergblock.php` - features relating to the new Gutenberg block
    - `dmg-post-finder.php` - PHP entrypoint for the plugin
    - Composer dependency files (for WordPress Coding Standards linting)
    - Node dependency files
    - ESLint configuration
- `wp/` - WordPress gets installed here
    - `wp/wp-content/debug.log` - the Dockerised WordPress is configured with `WP_DEBUG=true`, logging to this file
- `wp-data/initial-db.sql` - skeleton data used to populate the dev database
- `docker-compose.yml` - Docker configuration
- `20000-posts-for-testing.sql.gz` - GZipped SQL which, if run, will add 200,000 Posts to your `wp_posts` table
- `README.md` - this readme
