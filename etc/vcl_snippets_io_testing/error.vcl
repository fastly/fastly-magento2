  if (obj.status == 798 ) {
    set obj.http.Content-Type = "text/html; charset=utf-8";
    set obj.status = 200;
    synthetic {"<HTML>
      <HEAD>
      <TITLE>Image Optimization Test</TITLE>
      <META HTTP-EQUIV='Content-Type' CONTENT='text/html;'>
      </HEAD>
      <BODY>
      <form>
        Enter the quality level you want to test. This will set a cookie that will
        allow you to test the site with the said quality level. If you want to try a different
        quality level come back to this page. Also best used in Incognito or Private Window mod
        <p>
        <h2>Quality level 0-100 <input name=quality size=3></h2>
        <input type=submit>
      </form>
      </BODY>
      </HTML>"};
      return (deliver);
  }
  
  if (obj.status == 799 ) {
    set obj.http.Content-Type = "text/html; charset=utf-8";
    set obj.http.Set-Cookie = "fastly-io-test=" req.url.qs "; Max-Age=3600; path=/; HttpOnly";
    set obj.status = 200;
    synthetic {"Cookie has been set for 1 hour. Please browse the site as you normally would."};
    return (deliver);
  }
  
  
