{* text *}

{* {extends file='page.tpl'} *}
{$printJobs}
{* {block name="page_content"} *}
  {* {foreach from=$printJobs item=printjob}
    <option value="{$printjob.teccode}" title="{$printjob.name}">{$printjob.name}</option>
  {/foreach} *}

{* {/block} *}