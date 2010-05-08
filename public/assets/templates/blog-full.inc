/*
 * Preview for the Blog class
 *
 * LICENSE: This source file is subject to the MIT License, available at
 * http://www.opensource.org/licenses/mit-license.html
 *
 * @author      Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright   2010 Ennui Design
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 * @see         Blog class <ennui-cms/inc/class.blog.inc.php>
 *
 * Available header template tags:
 *  title           Page title displayed above
 *
 * Available entry template tags:
 *  site-url        The base URL for the site (i.e. http://yoursite.com/)
 *  page            Page name (for URLs)
 *  title           The title of the entry
 *  body            The body of the entry
 *  tags            Tags for the entry
 *  category-name   The name of the category
 *  admin           Administrative options for the entry
 *
 * Available footer template tags:
 *  pagination      Controls to navigate between pages of entry previews
 */

// Entry loop
{loop}
<div id="blog"><!-- begin #blog -->
    <div class="post"><!-- begin .blog-post -->

        <!-- POST TITLE -->
        <h2><a href="#">Feast -> Fast</a></h2>
        <!-- POST CONTENT -->
        <div class="content">
            {body}
        </div>
        <!-- POST METADATA -->
        <p class="meta">
                <span class="date">{date} by {author}</span>
                <span class="tags">{tags}</span>
                <span class="comments">{comment-count} {comment-text}</span>
                <!--<span class="fb">Become a Fan</span>
                <span class="tweet_this">Tweet this!</span>-->
        </p>
    </div><!-- end .blog-post -->
</div><!-- end #blog -->
{comments}
{/loop}

// Footer
<a href="{backlink}">&laquo; back to previous page</a>