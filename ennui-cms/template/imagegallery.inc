                    <ul class="thumbbox">
{loop [
        "max_entries" : 2,
        "htmlentities" : true
    ]}
                        <li>
                            <a href="{image}">
                                <img src="{thumb}"
                                     alt="{caption}" />
                            </a>
                        </li>
{/loop}
                    </ul>