    <div id="comments">
        <h2> Comments for This Entry </h2>
        <ol>
{loop}
                <li id="comment-{id}">
                    <!-- USER INFO -->
                    <p class="user_info">
                        <!-- AVATAR -->
                        {image}
                        <span class="user">{user}</span>
                    </p>
                    <!-- COMMENT -->
                    <p class="comment">
                        {comment}
                    </p>
                    <!-- COMMENT DATE -->
                    <p class="comment_date">
                        Posted on {date}
                        [<a href="{site-url}{page}/{url}#comment-{id}">permalink</a>]
                    </p>
                </li>
{/loop}
        </ol>
        {comment-form}
    </div><!-- end comments -->