<article class="galleries">
    <h2>Photo Gallery</h2>
    <h3>Latest Albums</h3>
{loop}
    <div class="gallery">
        <a href="/{page}/{url}"><img src="{preview}" alt="{title}" /></a>
        <h4><a href="/{page}/{url}">{title}</a></h4>
        <p>{text-preview}</p>{admin}
    </div>
{/loop}
</article>