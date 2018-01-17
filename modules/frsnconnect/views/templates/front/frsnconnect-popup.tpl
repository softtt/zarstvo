{*
* Social Network connect modules
* frsnconnect 0.15 by froZZen
*}
<!DOCTYPE html>
<html>
    <head>
    {literal}
    <script type="text/javascript">
        if (window.opener && !window.opener.closed) {
    {/literal}
            window.opener.OnCloseAuthPopup({$json});
    {literal}    
            window.opener.focus;
            window.close();
        }
    </script>
    {/literal}
    </head>
    <body>
        <h4>Redirecting back to the application...</h4>
    </body>
</html>