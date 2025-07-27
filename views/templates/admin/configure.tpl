
<form action="{$form.action}" method="post" enctype="multipart/form-data" class="form-horizontal">

    <div class="panel">
        <div class="panel-heading">
            {if isset($legend.icon) && $legend.icon != '' } <i class="{$legend.icon}"></i> {/if}
            {$legend.title}
        </div>

        {* DISPLAYING INPUT FIELDS *}
        {foreach from=$form.input item=field}

            {if $field.type === 'file'}

                {* FILE *}
                <div class="form-group">
                    <label for="{$field.name}" class="control-label col-lg-3">
                        {$field.label}
                        {if isset($field.required) && $field.required }<sup class="required">*</sup>{/if}
                    </label>
                    <div class="col-lg-9">

                        <div class="form-group">
                            <div class="col-lg-6">
                                <input id="{$field.id}"
                                       type="file"
                                       name="{$field.name}"
                                       data-text-input="#{$field.id}_name"
                                       {if isset($field.extensions_allowed) } accept="{$field.extensions_allowed}" {/if}
                                       class="hide"
                                       {if isset($field.required) && $field.required } required="required" {/if}
                                />
                                <div class="dummyfile input-group">
                                    <span class="input-group-addon"><i class="icon-file"></i></span>
                                    <input id="{$field.id}_name" type="text" class="disabled" readonly="" aria-label="{$field.label}">
                                    <span class="input-group-btn">
                                        <button type="button"
                                                class="btn btn-default"
                                                data-file-input="#{$field.id}">
                                            <i class="icon-folder-open"></i> {$field.button_label}
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        {if isset($field.image_thumbnail_path) && $field.image_thumbnail_path != ""}
                            <div style="margin-top:10px;">
                                <img src="{$field.image_thumbnail_path}" alt="{$field.image_thumbnail_alt }" class="img-thumbnail"
                                     style="width:auto; max-width:100%; max-height:150px; aspect-ratio: initial;" />
                            </div>
                        {/if}

                        {if $field.desc != '' }
                            <p class="help-block">{$field.desc}</p>
                        {/if}

                    </div>
                </div>

            {elseif $field.type === 'text'}

                {* TEXT *}
                <div class="form-group">
                    <label for="{$field.name}" class="control-label col-lg-3">
                        {$field.label}
                        {if isset($field.required) && $field.required }<sup class="required">*</sup>{/if}
                    </label>
                    <div class="col-lg-9">
                        <input type="text" name="{$field.name}"
                               value="{$field.value|escape:'html':'UTF-8'}"
                               class="form-control"
                               size="60"
                               {if isset($field.required) && $field.required } required="required" {/if}
                        />

                        {if $field.desc != ""}
                            <p class="help-block">
                                {$field.desc}
                            </p>
                        {/if}

                    </div>
                </div>

            {elseif $field.type === 'textarea'}

                {* TEXTAREA *}
                <div class="form-group">
                    <label for="{$field.name}" class="control-label col-lg-3">
                        {$field.label}
                        {if isset($field.required) && $field.required }<sup class="required">*</sup>{/if}
                    </label>
                    <div class="col-lg-9">
                        <textarea name="{$field.name}"
                                  class="form-control"
                                  rows="4"
                                  {if isset($field.required) && $field.required } required="required" {/if}
                        >{$field.value|escape:'html':'UTF-8'}</textarea>

                        {if $field.desc != ""}
                            <p class="help-block">
                                {$field.desc}
                            </p>
                        {/if}

                    </div>
                </div>

            {/if}

        {/foreach}


        <div class="panel-footer">
            <button type="submit" name="{$form.submit.name}" class="btn btn-primary pull-right">
                {if isset($form.submit.icon) && $form.submit.icon != '' } <i class="{$form.submit.icon}"></i> {/if}
                {$form.submit.title}
            </button>
        </div>

    </div>

    {* TOKEN *}
    <input type="hidden" name="token" value="{$form.token}" />
</form>
