
{if isset($pb_display_module) && $pb_display_module}

    <div class="card text-center p-3 my-4">
        {if $pb_img}
            <div class="mb-3">
                <img src="{$pb_img|escape:'html':'UTF-8'}"
                     alt="{$pb_title|escape:'html':'UTF-8'}"
                     class="img-fluid" />
            </div>
        {/if}

        <h2 class="h4 mb-2">{$pb_title|escape:'html':'UTF-8'}</h2>
        <p>{$pb_text|escape:'html':'UTF-8'}</p>
    </div>

{/if}
