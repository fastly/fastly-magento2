    if (obj.status == 971) {
        set obj.http.Content-Type = "text/html; charset=utf-8";
        set obj.http.WWW-Authenticate = "Basic realm=Secured";
        set obj.status = 401;
        synthetic {"<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" 
        "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
        <HTML>
        <HEAD>
        <TITLE>Error</TITLE>
        <META HTTP-EQUIV='Content-Type' CONTENT='text/html;'>
        </HEAD>
        <BODY><H1>401 Unauthorized</H1></BODY>
        </HTML>
        "};
        return (deliver);
    }

