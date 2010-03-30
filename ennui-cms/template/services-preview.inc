<h2>Our Services</h2>
{loop ["max":4]}
<h3><a href="/{page}/{url}">{title}</a></h3>
<p>{preview}{admin}</p>
{/loop}
<p>This paragraph is outside the loop.</p>