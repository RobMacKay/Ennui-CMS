    <div id="comments">
        <h2> Comments for This Entry </h2>
{loop [
        "strip_tags" : true
    ]}
        <div id="comment-{id}">
            <div class="comment-poster">
                <p>
                    {gravatar}
                    Posted by <span class="poster">{poster}</span> on {date}.
                    <a href="{site-url}{page}/{url}#comment-{id}">permalink</a>
                </p>
            </div>
            <div class="comment-body">
                {comment-body}
            </div>
        </div>
{/loop}
        <h3> Post a Comment </h3>
        <p class="get_gravatar">
            Want to show your face?
            <a href="http://gravatar.com" rel="external">Get a gravatar!</a>
        </p>

    </div><!-- end comments -->