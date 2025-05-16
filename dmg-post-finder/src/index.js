/**
 * JavaScript code for the DMG Post Finder block.
 * When added to a page, it allows the user to search for and select a post (by post ID or title). The selected
 * post is then displayed in the block as a hyperlink..
 */

/**
 * WordPress dependencies
 */
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  Button,
  Spinner,
  Placeholder,
} from "@wordpress/components";
import { useState, useEffect } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";

/**
 * Register the block
 */
registerBlockType("dmg/post-finder", {
  title: __("DMG Post Finder"),
  icon: "search",
  category: "widgets",
  attributes: {
    postId: {
      type: "number",
      default: null,
    },
    postTitle: {
      type: "string",
      default: "",
    },
    postUrl: {
      type: "string",
      default: "",
    },
  },

  /**
   * The edit function for the block
   * @param {Object}   root               - Object passed to the edit function, containing:
   * @param {Object}   root.attributes    - Block attributes containing postId, postTitle, and postUrl.
   * @param {Function} root.setAttributes - Function to update block attributes.
   */
  edit: ({ attributes, setAttributes }) => {
    const { postId, postTitle, postUrl } = attributes;
    const [searchTerm, setSearchTerm] = useState("");
    const [isSearching, setIsSearching] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [showingRecentPosts, setShowingRecentPosts] = useState(true);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);

    const blockProps = useBlockProps();

    /**
     * Load recent posts when the component mounts
     */
    useEffect(() => {
      loadRecentPosts();
    }, []);

    /**
     * Utility function to perform a search, used both when loading recent posts (call with no parameters) and when
     * searching for a specific term (call with a search term and optional page number).
     *
     * @param {string} performSearchTerm - The search term to use for the search. Leave blank to load recent posts.
     * @param {number} performSearchPage - The page number to use for the search. Defaults to 1.
     * @return {Promise} A promise that resolves to the search results, or rejects with an error.
     */
    const performSearch = (performSearchTerm = "", performSearchPage = 1) => {
      setIsSearching(true);

      return new Promise((resolve, reject) => {
        apiFetch({
          path:
            "/dmg-post-finder/v1/search" +
            "?search=" +
            encodeURIComponent(performSearchTerm) +
            "&page=" +
            performSearchPage,
          method: "GET",
        })
          .then((response) => {
            setSearchResults(response.posts);
            setTotalPages(response.pages);
            setIsSearching(false);
            resolve(response);
          })
          .catch((error) => {
            console.error("Error searching posts: ", error); // eslint-disable-line no-console -- acceptable use of console.error for error logging
            setIsSearching(false);
            reject(error);
          });
      });
    };

    /**
     * Load recent posts for initial display
     */
    const loadRecentPosts = () => {
      performSearch(); // a default search fetches the most-recent posts with no search term
    };

    /**
     * Handle post search
     */
    const handleSearch = () => {
      setPage(1); // reset to page 1 on new search
      performSearch(searchTerm, page).then(() => {
        setShowingRecentPosts(false); // if we were previously showing recent posts, we need to update the state because we're now showing search results
      });
    };

    /**
     * Handle page change in pagination
     * @param {number} newPage - The new page number to navigate to.
     */
    const handlePageChange = (newPage) => {
      setIsSearching(true); // show the spinner while repaginating so the user knows something is happening
      setPage(newPage);

      // Trigger new search with updated page
      apiFetch({
        path:
          "/dmg-post-finder/v1/search" +
          "?search=" +
          encodeURIComponent(searchTerm) +
          "&page=" +
          newPage,
        method: "GET",
      })
        .then((response) => {
          setSearchResults(response.posts);
          setTotalPages(response.pages);
          setIsSearching(false);
        })
        .catch((error) => {
          console.error("Error searching posts: ", error); // eslint-disable-line no-console -- acceptable use of console.error for error logging
        });
    };

    /**
     * Handle post selection
     * @param {Object} post - The post object containing id, title, and url.
     */
    const selectPost = (post) => {
      setAttributes({
        postId: post.id,
        postTitle: post.title,
        postUrl: post.url,
      });
    };

    /**
     * Render post list items
     * @param {Array} posts - Array of posts to render.
     */
    const renderPostList = (posts) => {
      if (!posts || posts.length === 0) {
        return <p>{__("No posts found.")}</p>;
      }

      return (
        <ul className="dmg-post-finder__results">
          {posts.map((post) => (
            <li key={post.id}>
              <Button variant="link" onClick={() => selectPost(post)}>
                {post.title}
              </Button>
            </li>
          ))}
        </ul>
      );
    };

    return (
      <>
        <InspectorControls>
          <PanelBody title={__("Post Search")}>
            <TextControl
              label={__("Search posts")}
              value={searchTerm}
              onChange={setSearchTerm}
              onKeyDown={(e) => {
                if (e.key === "Enter") {
                  handleSearch();
                }
              }}
              placeholder={__("Search by title or ID…")}
              type="search"
            />

            <p>
              <Button
                variant="primary"
                onClick={handleSearch}
                disabled={isSearching || !searchTerm}
              >
                {isSearching ? __("Searching…") : __("Search")}
              </Button>

              {isSearching && <Spinner />}
            </p>

            {searchResults.length > 0 && (
              <div className="dmg-post-finder__search-results">
                <h3>
                  {showingRecentPosts
                    ? __("Recent Posts")
                    : __("Search Results")}
                </h3>
                {renderPostList(searchResults)}

                {totalPages > 1 && (
                  <div className="dmg-post-finder__pagination">
                    {Array.from({ length: totalPages }, (_, i) => i + 1).map(
                      (pageNum) => (
                        <Button
                          key={pageNum}
                          variant={pageNum === page ? "primary" : "secondary"}
                          onClick={() => handlePageChange(pageNum)}
                          style={{ margin: "0 4px" }}
                        >
                          {pageNum}
                        </Button>
                      ),
                    )}
                  </div>
                )}
              </div>
            )}

            {!isSearching && searchTerm && searchResults.length === 0 && (
              <p>{__("No posts found.")}</p>
            )}
          </PanelBody>
        </InspectorControls>

        <div {...blockProps}>
          {postId ? (
            <p className="dmg-read-more">
              Read More: <a href={postUrl}>{postTitle}</a>
            </p>
          ) : (
            <Placeholder
              icon="search"
              label={__("DMG Post Finder")}
              instructions={__(
                "Search for and select a post in the block settings.",
              )}
            />
          )}
        </div>
      </>
    );
  },

  /**
   * Save function returns null since we're using a server-side render
   */
  save: () => {
    return null;
  },
});
