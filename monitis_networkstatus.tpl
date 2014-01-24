<script type="text/javascript" src="includes/jscript/statesdropdown.js"></script>


{if $noregistration}

    <div class="alert alert-error">
        <p>{$LANG.registerdisablednotice}</p>
    </div>

{else}

{if $errormessage}
<div class="alert alert-error">
    <p class="bold">{$LANG.clientareaerrors}</p>
    <ul>
        {$errormessage}
    </ul>
</div>
{/if}


{/if}

{php}
include('modules/addons/monitis_addon/clientui/networkstatus.php');
{/php}

<br />
<br />