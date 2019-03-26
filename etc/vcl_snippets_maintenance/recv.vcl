if (table.lookup(magentomodule_config, "allow_super_users_during_maint", "0") == "1" && !req.http.Fastly-Client-Ip ~ maint_allowlist ) {
    set req.http.ResponseObject = "750";
}