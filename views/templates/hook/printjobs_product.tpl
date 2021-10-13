<ul class="nav nav-tabs" id="nav-tabs-printjobs">
    {assign var="cliche" value=1}
    {foreach from=$printjobs item=printjob name=rows}
        {assign var="counter" value=$smarty.foreach.rows.iteration}
        <li class="nav-item col-xs-{if count($printjobs) > 4}{(12/count($printjobs))|string_format:'%d'}{else}3{/if}">
            {if $printjob.areaimg}
            <a class="nav-link {if $activar == $counter}active{elseif $printjob.areacode == $activar}in active{/if}" id="{$printjob.areacode}-tab" data-toggle="tab" href="#tab{$printjob.areacode}" role="tab" aria-controls="tab{$printjob.areacode}" aria-selected="true">
                <img id="image_{$printjob.areaimg}" src="{$printjob.areaimg}" title="{$printjob.areaname}" width="100%">
            </a>    
            {/if}
        </li>
    {/foreach}   
</ul>
<div class="tabs" id="form-printjobs">
    <input type="hidden" name="reference" id="product_reference" value="{$reference}">
    <div class="tab-content" id="tabcontent-printjobs">
        {foreach from=$printjobs item=printjob name=rows}
            {assign var="counter" value=$smarty.foreach.rows.iteration}
            <div class="areacode tab-pane fade {if  $activar == $counter}in active{elseif $printjob.areacode == $activar}in active{/if}" data-areacode="{$printjob.areacode}"
            id="tab{$printjob.areacode}" role="tabpanel" aria-labelledby="{$printjob.areacode}-tab">
                <div class="row">
                    
                    <div class="col-md-12">

                        {* <div class="btn-group-toggle" data-toggle="buttons"> *}
                            <label class="btn btn-outline-success {if $printjob.active} active {/if}" for="areacode_{$printjob.areacode}">
                                <input type="checkbox" class="areacode" name="areacode[{$printjob.areacode}]"
                                    id="areacode_{$printjob.areacode}" value="{$printjob.areacode}" autocomplete="off"
                                    {if $printjob.active} checked {/if}>
                                   {$printjob.areaname}
                            </label>
                        {* </div> *}
                    </div>
                   
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="teccode_{$printjob.areacode}">Type print</label>
                            <select class="form-control typeprint" id="teccode_{$printjob.areacode}"
                                name="teccode[{$printjob.areacode}]" {if !$printjob.active} disabled {/if}>

                                {foreach from=$printjob.printjobs item=teccode}
                                    <option value="{$teccode.teccode}" title="{$teccode.name}"
                                        {if $teccode.teccode == $printjob.teccode} 
                                                selected 
                                        {/if}
                                        
                                    >
                                        {$teccode.name}
                                    </option>
                                {/foreach}

                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="qcolors_{$printjob.areacode}">Colors</label>
                            <select class="form-control qcolor" id="qcolors_{$printjob.areacode}" 
                            name="qcolors[{$printjob.areacode}]" {if !$printjob.active} disabled {/if}>
                                {for $foo=1 to $printjob.maxcolour}
                                <option value="{$foo}" 
                                    {assign var="qcolors" value="qcolors_"|cat:$printjob.areacode}
                                        {if $foo==$printjob.qcolors} 
                                        selected="selected"
                                        {/if} 
                                    >

                                    {$foo} - {if $foo==1} {l s="color" mod='rj_makitosync'}{else}{l s="Colors" mod='rj_makitosync'}{/if}
                                </option>
                                {/for}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-control-label" for="areawidth_{$printjob.areacode}">width</label>
                            <input type="number" class="form-control width" id="areawidth_{$printjob.areacode}"
                                name="areawidth[{$printjob.areacode}]"  aria-describedby="areawidth_{$printjob.areacode}"
                                min="1" max="{$printjob.areawidth|string_format:"%.2f"}"
                                value="{$printjob.areawidth|string_format:"%.2f"}"
                                {if !$printjob.active} disabled{/if}>cm
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-control-label" for="areahight_{$printjob.areacode}">heigth</label>
                            <input type="number" class="form-control heigth" id="areahight_{$printjob.areacode}"
                                name="areahight[{$printjob.areacode}]"  aria-describedby="areahight_{$printjob.areacode}"
                                min="1" max="{$printjob.areahight|string_format:"%.2f"}"
                                value="{$printjob.areahight|string_format:"%.2f"}" 
                                {if !$printjob.active} disabled{/if}>cm
                                
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        {* <div class="btn-group btn-group-toggle" data-toggle="buttons"> *}
                            <label class="btn btn-outline-success {if $printjob.clicheactive == 1} active{/if}" for="cliche_{$printjob.areacode}">
                                <input class="btn-check" type="radio" name="cliche[{$printjob.areacode}]" autocomplete="off"
                                id="cliche_{$printjob.areacode}" data-value="{$printjob.cliche|string_format:"%.2f"}" value="1" 
                                {if $printjob.clicheactive == 1} checked{/if}  
                                {if !$printjob.active} disabled {/if}>
                                {l s='Cliché' mod='rj_makitosync'} = <span>{$printjob.cliche|string_format:"%.2f"}</span> €
                            </label>
                            <label class="btn btn-outline-success {if $printjob.clicheactive == 2} active{/if}" for="clicherep_{$printjob.areacode}">
                                <input class="btn-check" type="radio" name="cliche[{$printjob.areacode}]" autocomplete="off"
                                id="clicherep_{$printjob.areacode}" data-value="{$printjob.clicherep|string_format:"%.2f"}" value="2" 
                                {if $printjob.clicheactive == 2} checked{/if} 
                                {if !$printjob.active} disabled {/if}>
                                {l s='Repetición Cliché' mod='rj_makitosync'} = <span>{$printjob.clicherep|string_format:"%.2f"}</span> €
                            </label>
                        {* </div> *}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="current-price-print text-right">
                            <h4>
                                {l s='Price' mod='rj_makitosync'}:
                                <span class="price-print" id="price-print_{$printjob.areacode}">{$printjob.priceprint}</span> €
                                <input type="hidden" id="price_{$printjob.areacode}" name="price[{$printjob.areacode}]" value="{$printjob.priceprint}" {if !$printjob.active} disabled {/if}>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        {/foreach} 
    </div>
</div>
{$idProduct|dump}
{$dataget|dump}
{$getvalues|dump}


