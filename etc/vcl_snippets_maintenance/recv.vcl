if (table.lookup(magentomodule_config, "allow_super_users_during_maint", "1") && !req.http.Fastly-Client-IP ~ maint_allowlist ) {
    set req.http.ResponseObject = "970";
}