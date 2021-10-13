{foreach from=$customizations item=customization}
<div class="row">
    <div class="col-md-4">
        <img src="{$customization.areaimg|escape:'htmlall':'UTF-8'}" width="150px">
    </div>
    <div class="col-md-8">
        <h4>{$customization.areaname|escape:'htmlall':'UTF-8'}</h4>
        <ul>
            <li><b>{l s='Type print' mod='rj_makitosync'}:</b> {$customization.typeprint|escape:'htmlall':'UTF-8'}</li>
            <li><b>{l s='Width' mod='rj_makitosync'}:</b> {$customization.areawidth|escape:'htmlall':'UTF-8'}</li>
            <li><b>{l s='Hight' mod='rj_makitosync'}:</b> {$customization.areahight|escape:'htmlall':'UTF-8'}</li>
            <li><b>{l s='Cant. Colors' mod='rj_makitosync'}:</b>{$customization.qcolors|escape:'htmlall':'UTF-8'}</li>
            <li><b>{l s='Cliche' mod='rj_makitosync'}:</b> {if $customization.cliche|escape:'htmlall':'UTF-8' == '1'}nuevo cliche{else}repetir cliche{/if} </li>
            <li><b>{l s='Quantity' mod='rj_makitosync'}:</b> {$customization.qty|escape:'htmlall':'UTF-8'}</li>
        </ul>
    </div>
</div>
<hr>
{/foreach}