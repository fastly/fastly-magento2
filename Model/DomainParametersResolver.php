<?php

declare(strict_types=1);

namespace Fastly\Cdn\Model;

/**
 * Class ExtractDomainParameters
 * @package Fastly\Cdn\Model
 */
class DomainParametersResolver
{
    /**
     * @param \stdClass $params
     * @return \stdClass
     */
    public function combineDataAndIncluded(\stdClass $params): \stdClass
    {
        foreach ($params->data as $domain) {
            $domain->tls_subscriptions['id'] = $domain->relationships->tls_subscriptions->data[0]->id;
            if (!empty($domain->relationships->tls_activations->data)) {
                $domain->tls_activations['id'] = $domain->relationships->tls_activations->data[0]->id;
            } else {
                $domain->tls_activations = false;
            }
            if (!empty($domain->relationships->tls_certificates->data)) {
                $domain->tls_certificates['id'] = $domain->relationships->tls_certificates->data[0]->id;
            } else {
                $domain->tls_certificates = false;
            }
            $domain->tls_authorization = false;
            foreach ($params->included as $key => $include) {
                if ($include->id !== $domain->tls_subscriptions['id']) {
                    if ($include->id !== $domain->tls_activations['id']) {
                        if ($include->id !== $domain->tls_certificates['id']) {
                            if ($domain->tls_authorization && $domain->tls_authorization['id'] === $include->id) {
                                $domain->tls_authorization['state'] = $include->attributes->state;
                                $domain->tls_authorization['challenges'] = $include->attributes->challenges;
                            }
                            continue;
                        }

                        $domain->tls_certificates['certificate_name'] = $include->attributes->name;
                        continue;
                    }

                    $domain->tls_activations['created_at'] = $include->attributes->created_at;
                    continue;
                }

                $domain->tls_subscriptions['state'] = $include->attributes->state;
                if (!empty($include->relationships->tls_authorizations->data)) {
                    $domain->tls_authorization['id'] = $include->relationships->tls_authorizations->data[0]->id;
                } else {
                    $domain->tls_authorization = false;
                }
            }
        }

        return $params;
    }
}
