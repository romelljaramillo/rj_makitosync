{extends file='page.tpl'}

{block name="page_content"}
  {* <p class="alert {if $variables.nw_error}alert-danger{else}alert-success{/if}">
    {$variables.msg} *}
    <pre>
    {$variables|print_r}
    </pre>
  {* </p> *}
{/block}

