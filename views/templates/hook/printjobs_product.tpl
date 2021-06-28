<div class="accordion" id="accordionPrintJobs">
    <input type="hidden" name="productreference_printjobs" id="productreference_printjobs" value="{$printjobs[0].reference}">
    {assign var="cliche" value=1}
    
    {foreach from=$printjobs item=printjob}
    <div class="card areacode" data-areacode="{$printjob.areacode}">
        <!-- header item -->
        <div class="card-header" id="heading{$printjob.areacode}">
            <input type="checkbox" class="form-check-input pull-right printarea" name="printArea_{$printjob.areacode}"
                id="printArea_{$printjob.areacode}" value="{$printjob.areacode}" 
                {if array_key_exists("printArea_"|cat:$printjob.areacode, $dataselect)} checked {/if}>
            <h4 class="mb-0">
                <button class="btn btn-link btn-block btn-header" type="button" data-toggle="collapse"
                    data-target="#collapse{$printjob.areacode}" aria-expanded="true"
                    aria-controls="collapse{$printjob.areacode}">
                    <div class="row">
                        <div class="col-md-2">
                            {if $printjob.areaimg}
                            <img id="image_{$printjob.areaimg}" src="{$printjob.areaimg}" title="{$printjob.areaname}"
                                width="40px">
                            {/if}
                        </div>
                        <div class="col-md-8">
                            <p class="areaname">{$printjob.areaname}</p>
                        </div>
                        <div class="col-md-2">
                            <i class="material-icons">expand_more</i>
                        </div>
                    </div>
                </button>
            </h4>
        </div>
        <!-- body item -->

        <div id="collapse{$printjob.areacode}" class="collapse show" aria-labelledby="heading{$printjob.areacode}"
            data-parent="#accordionPrintJobs">
            <div class="card-body">
                <div class="panel-body">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="teccode_{$printjob.areacode}">Type print</label>
                                    <select class="form-control typeprint" id="teccode_{$printjob.areacode}"
                                        name="teccode_{$printjob.areacode}" {if !in_array($printjob.areacode,
                                        $dataselect)} disabled {/if}>

                                        {foreach from=$printjob.printjobs item=teccode}
                                            <option value="{$teccode.teccode}" title="{$teccode.name}"
                                                {if in_array($teccode.areacode, $dataselect.areacode)}
                                                    {if $teccode.teccode == $dataselect["teccode_"|cat:$teccode.areacode]} 
                                                        selected 
                                                        {$printjob.maxcolour = $teccode.maxcolour}
                                                        {$printjob.areawidth = $dataselect["width_"|cat:$teccode.areacode]}
                                                        {$printjob.areahight = $dataselect["heigth_"|cat:$teccode.areacode]}
                                                        {$printjob.cliche = $teccode.cliche}
                                                        {$printjob.clicherep = $teccode.clicherep}
                                                        {$cliche = $dataselect["cliche_"|cat:$teccode.areacode]}
                                                    {/if}
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
                                    name="qcolors_{$printjob.areacode}" {if !in_array($printjob.areacode,
                                    $dataselect)} disabled {/if}>
                                        {for $foo=1 to $printjob.maxcolour}
                                        <option value="{$foo}" 
                                            {assign var="qcolors" value="qcolors_"|cat:$printjob.areacode}
                                            {if array_key_exists("qcolors_"|cat:$printjob.areacode, $dataselect)} 
                                                {if $foo==$dataselect.$qcolors} 
                                                selected="selected"
                                                {/if} 
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
                                    <label class="form-control-label" for="width_{$printjob.areacode}">width</label>
                                    <input type="number" class="form-control width" id="width_{$printjob.areacode}"
                                        name="width_{$printjob.areacode}"  aria-describedby="width_{$printjob.areacode}"
                                        min="1" max="{$printjob.areawidth|string_format:"%.2f"}"
                                        value="{$printjob.areawidth|string_format:"%.2f"}"
                                        {if !in_array($printjob.areacode, $dataselect)} disabled{/if}>cm
                                        
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label" for="heigth_{$printjob.areacode}">heigth</label>
                                    <input type="number" class="form-control heigth" id="heigth_{$printjob.areacode}"
                                        name="heigth_{$printjob.areacode}"  aria-describedby="heigth_{$printjob.areacode}"
                                        min="1" max="{$printjob.areahight|string_format:"%.2f"}"
                                        value="{$printjob.areahight|string_format:"%.2f"}" 
                                        {if !in_array($printjob.areacode, $dataselect)} disabled{/if}>cm
                                        
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="cliche_{$printjob.areacode}"
<<<<<<< HEAD
                                        id="cliche_{$printjob.areacode}" data-value="{$printjob.cliche|string_format:"%.2f"}" value="1" 
=======
                                        id="cliche_{$printjob.areacode}" value="1" 
>>>>>>> 4f53d2985b008dbd61bfc5e2043b25788ba70ac0
                                        {if $cliche == 1} checked{/if}  
                                        {if !in_array($printjob.areacode, $dataselect)} disabled {/if}>
                                    <label class="form-check-label" for="cliche_{$printjob.areacode}">
                                        Cliché = {$printjob.cliche|string_format:"%.2f"}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="cliche_{$printjob.areacode}" 
                                        id="clicherep_{$printjob.areacode}" data-value="{$printjob.clicherep|string_format:"%.2f"}" value="2" 
                                        {if $cliche == 2} checked{/if} 
                                        {if !in_array($printjob.areacode, $dataselect)} disabled {/if}>
                                    <label class="form-check-label" for="clicherep_{$printjob.areacode}">
                                        Repetición Cliché = {$printjob.clicherep|string_format:"%.2f"}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <div class="current-price">
                                    <h4 class="text-right">Price:
                                        <span class="price-print" id="price-print_{$printjob.areacode}" content="0.00">0.00 €</span>
                                        <input type="hidden" id="price_{$printjob.areacode}" name="price_{$printjob.areacode}">
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {/foreach}
</div>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', () => {
        
    });
</script>