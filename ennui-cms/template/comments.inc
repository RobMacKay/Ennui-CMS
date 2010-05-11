    <div id="comments">
        <h2> Comments for This Entry </h2>
        <ol>
{loop}
                <li id="comment-{id}">
                    <!-- AVATAR -->
                    {image}
                    <!-- USER NAME -->
                    <p class="user_name">
                        <span class="user">{user}</span> says:
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