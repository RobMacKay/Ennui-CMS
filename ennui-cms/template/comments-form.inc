    <form id="add-comment"  method="post" action="{form-action}">
        <fieldset>
            <legend>Post a Comment</legend>
            <p class="get_gravatar">
                Want to show your face?
                <a href="http://gravatar.com" rel="external">Get a gravatar!</a>
            </p>
            <label for="cmnt_name">Name (required)</label>
            <input type="text" id="cmnt_name" name="cmnt_name"
                value="{name}" class="commentInput{text-err}" />
            <label for="cmnt_email">Email (required, not shared)</label>
            <input type="text" id="cmnt_email" name="cmnt_email"
                value="{email}" class="commentInput{text-err}" />
            <label for="cmnt_link">Website (optional)</label>
            <input type="text" id="cmnt_link" name="cmnt_link"
                value="{link}" class="commentInput" />
            {challenge}
            <label for="cmnt_txt">Enter your comment here.</label>
            <textarea id="cmnt_txt" name="cmnt_txt" rows="10" cols="45"
                class="commentTextarea{text-err}">{text}</textarea>
            <input type="checkbox" name="cmnt_sub" id="cmnt_subscribe"
                value="subscribe" />
            <label for="cmnt_subscribe">
                Notify me of replies to this post via email.
            </label>
            <input type="hidden" id="cmnt_bid" name="cmnt_bid"
                value="{bid}" />
            <input type="hidden" name="action" value="cmnt_post" />
            <input type="hidden" name="token"
                   value="{token}" />
            <input type="submit" id="cmnt_sub" class="commentSubmit"
                value="Post Comment" />
        </fieldset>
    </form>