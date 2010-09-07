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

// Header
<div class="blog-post">
<ul id="entries">
// Entry loop
{loop [
        "text_preview" : true,
        "text_preview_length" : 50
    ]}
    <li class="post">
        <a href="/{page}/{url}">
            <img src="{thumb}" alt="{title}" class="blog_img" />
        </a>
        <h2><a href="/{page}/{url}">{title}</a></h2>
        {body}{admin}
        <!-- POST METADATA -->
        <p class="meta">
                <span class="date">{date} by {author}</span>
                <span class="comments">
                    <a href="/{page}/{url}#comments">{comment-count} {comment-text}</a>
                </span><br />
                <span class="tags">This entry was tagged with {tags}</span>
        </p>
        <!-- end sharing options -->
    </li>
{/loop}

// Footer
</ul>
{pagination}
 </div><!-- end .blog-post -->