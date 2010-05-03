<article class="galleries">
    <h2>Photo Gallery</h2>
    <h3>{title}</h3>
{loop}
    <div class="gallery">
        <a href="/{page}/{url}"><img src="{thumb}" alt="{title}" /></a>
        <h4><a href="/{page}/{url}">{title}</a></h4>
        <p>
            <strong>Number of Photos:</strong> {photo-count}
        </p>{admin}
    </div>
{/loop}
    {backlink}
</article>