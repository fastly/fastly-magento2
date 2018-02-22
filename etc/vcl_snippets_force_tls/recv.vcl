# Force SSL immediately to avoid magento module VCL stripping off
# google campaign ids like gclid
  if (!req.http.Fastly-SSL) {     
    error 801 "Force SSL";
  }
