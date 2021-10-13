{foreach from=$customizations item=customization}
    Area Print: {$customization.areaname|escape:'htmlall':'UTF-8'} -
    Type print: {$customization.typeprint|escape:'htmlall':'UTF-8'} -  
    Width: {$customization.areawidth|escape:'htmlall':'UTF-8'} -  
    Hight: {$customization.areahight|escape:'htmlall':'UTF-8'} -  
    Cant. Colors: {$customization.qcolors|escape:'htmlall':'UTF-8'} -  
    Cliche: {if $customization.cliche|escape:'htmlall':'UTF-8' == '1'}nuevo cliche{else}repetir cliche{/if} -  
    Quantity: {$customization.qty|escape:'htmlall':'UTF-8'}
{/foreach}