<h2>Our Services</h2>
<p> A description. </p>
{loop [
        "max_entries":4,
        "add_first_entry_class":true
      ]}
<div class="entry-display {first} {last}">
<h3><a href="/{page}/{url}">{title}</a></h3>
{preview}{admin}
</div><!-- end .entry-display -->
{/loop}
<p>This paragraph is outside the loop.</p>