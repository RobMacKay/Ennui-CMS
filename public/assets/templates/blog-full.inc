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

            <a href="/{page}/{url}#add-comment"
               class="comments">Post a Comment</a>

            <span class="tags">It was tagged with {tags}</span>

            <!-- Tweet Button -->
            <script src="http://platform.twitter.com/widgets.js"
                    type="text/javascript"></script>
            <span class="tweet_this">
                <a href="http://twitter.com/share?via=ennuidesign&amp;url={encoded-url}&amp;text={encoded-title}&count=none"
                   class="twitter-share-button">Tweet</a>
            </span>
            <!-- end Tweet Button -->

            <!-- Facebook Like Button -->
            <span class="fb">
                <iframe src="http://www.facebook.com/plugins/like.php?href={encoded-url}&amp;layout=button_count&amp;show_faces=false&amp;width=450&amp;action=like&amp;colorscheme=light&amp;height=21"
                        scrolling="no" frameborder="0"
                        style="border:none; width:430px; height:40px;"
                        allowTransparency="true"></iframe>
            </span>
            <!-- end Facebook Like Button -->
        </p>
    </div><!-- end .blog-post -->
</div><!-- end #blog -->
{comments}
{/loop}
