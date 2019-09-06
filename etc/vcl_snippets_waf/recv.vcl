# Specify one or multiple ACLs that are allowed to bypass WAF blocking ie. no WAF rules are going to trigger for those IPs.
    if(####WAF_ALLOWLIST####) {
       set req.http.bypasswaf = "1";
    }
