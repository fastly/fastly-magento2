<?php

declare(strict_types=1);

namespace Fastly\Cdn\Model;

/**
 * Class ExtractDomainParameters
 * @package Fastly\Cdn\Model
 */
class ApiParametersResolver
{
    /**
     * @param \stdClass $params
     * @return \stdClass
     */
    public function combineDataAndIncludedDomains(\stdClass $params): \stdClass
    {
        foreach ($params->data as $domain) {
            $domain->tls_subscriptions['id'] = $domain->relationships->tls_subscriptions->data[0]->id;
            if (!empty($domain->relationships->tls_activations->data)) {
                $domain->tls_activations['id'] = $domain->relationships->tls_activations->data[0]->id;
            } else {
                $domain->tls_activations = false;
                $domain->tls_configurations = false;
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
                                unset($params->included[$key]);
                            }
                            continue;
                        }

                        $domain->tls_certificates['certificate_name'] = $include->attributes->name;
                        unset($params->included[$key]);
                        continue;
                    }

                    $domain->tls_activations['created_at'] = $include->attributes->created_at;
                    $domain->tls_configurations['id'] = $include->relationships->tls_configuration->data->id;
                    unset($params->included[$key]);
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

    /**
     * @param \stdClass $params
     * @return \stdClass
     */
    public function combineDataAndIncludedConfigurations(\stdClass $params): \stdClass
    {
        foreach ($params->included as $key => $record) {
            $params->data->{$record->attributes->record_type}[] = $record->id;
        }

        return $params;
    }
}
