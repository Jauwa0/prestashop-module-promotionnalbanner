
<div class="simplebanner card text-center p-3 my-4">
    {if $sb_img}
        <div class="mb-3">
            <img src="{$sb_img|escape:'html':'UTF-8'}"
                 alt="{$sb_title|escape:'html':'UTF-8'}"
                 class="img-fluid" />
        </div>
    {/if}

    <h2 class="h4 mb-2">{$sb_title|escape:'html':'UTF-8'}</h2>
    <p>{$sb_text|escape:'html':'UTF-8'}</p>
</div>
