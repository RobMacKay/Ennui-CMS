<div class="post">{admin}
    <h2>{title}</h2>
    {body}
    <form action="{form-processing}" method="post" id="contact">
        <fieldset id="cf">
            <legend> Contact {site-name} </legend>
            <label for="cf_n">Name</label>
            <input type="text" name="cf_n" id="cf_n" />
            <label for="cf_e">Email</label>
            <input type="text" name="cf_e" id="cf_e" />
            <label for="cf_p">Phone Number (optional)</label>
            <input type="text" name="cf_p" id="cf_p" />
            <label for="cf_m">Enter Your Message Here</label>
            <textarea name="cf_m" id="cf_m" rows="18" cols="35"></textarea>
            <input type="hidden" name="page" value="{page}" />
            <input type="hidden" name="action" value="contact_form" />
            <input type="submit" name="cf_s" id="cf_s"
                   value="Send This Message" />
        </fieldset>
    </form>
</div>