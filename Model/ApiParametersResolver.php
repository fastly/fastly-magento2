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
        foreach ($params->data as $key => $domain) {
            if (!empty($domain->relationships->tls_subscriptions->data)) {
                $domain->tls_subscriptions['id'] = $domain->relationships->tls_subscriptions->data[0]->id;
            } else {
                $domain->tls_authorizations = false;
                $domain->tls_subscriptions = false;
            }

            if (!empty($domain->relationships->tls_activations->data)) {
                $domain->tls_activations['id'] = $domain->relationships->tls_activations->data[0]->id;
            } else {
                $domain->tls_activations = false;
                $domain->tls_configuratios = false;
            }

            if (!empty($domain->relationships->tls_certificates->data)) {
                $domain->tls_certificates['id'] = $domain->relationships->tls_certificates->data[0]->id;
            } else {
                $domain->tls_certificates = false;
            }

            $params->data[$key] = $this->handleIncluded($domain, $params->included);
        }

        return $params;
    }

    /**
     * @param \stdClass $domain
     * @param array $included
     * @return \stdClass
     */
    private function handleIncluded(\stdClass $domain, &$included = []): \stdClass
    {
        foreach ($included as $key => $record) {
            if (!$domain->tls_subscriptions || $domain->tls_subscriptions['id'] !== $record->id) {
                $domain->tls_subscriptions['state'] = $record->attributes->state;
                $domain->tls_subscriptions['created_at'] = $record->attributes->created_at;
                $domain->tls_subscriptions['certificate_authority'] = $record->attributes->certificate_authority;
                if (!empty($record->relationships->tls_authorizations->data)) {
                    $domain->tls_authorizations['id'] = $record->relationships->tls_authorizations->data[0]->id;
                }

                unset($included[$key]);
                continue;
            }

            if ($domain->tls_activations && $domain->tls_activations['id'] === $record->id) {
                $domain->tls_activations['created_at'] = $record->attributes->created_at;
                if (!empty($record->relationships->tls_configuration->data)) {
                    $domain->tls_configurations['id'] = $record->relationships->tls_configuration->data->id;
                }

                unset($included[$key]);
                continue;
            }

            if ($domain->tls_authorizations && $domain->tls_authorizations['id'] === $record->id) {
                $domain->tls_authorizations['state'] = $record->attributes->state;
                $domain->tls_authorizations['created_at'] = $record->attributes->created_at;
                $domain->tls_authorizations['challenges'] = $record->attributes->challenges;
                unset($included[$key]);
                continue;
            }

            if ($domain->tls_certificates && $domain->tls_certificates['id'] === $record->id) {
                $domain->tls_certificates['created_at'] = $record->attributes->created_at;
                $domain->tls_certificates['issued_to'] = $record->attributes->issued_to;
                $domain->tls_certificates['issuer'] = $record->attributes->issuer;
                $domain->tls_certificates['name'] = $record->attributes->name;
                $domain->tls_certificates['not_after'] = $record->attributes->not_after;
                $domain->tls_certificates['signature_algorithm'] = $record->attributes->signature_algorithm;
                unset($included[$key]);
                continue;
            }
        }
        return $domain;
    }

    public function combineDataAndIncludedConfigurations(\stdClass $params): \stdClass
    {
        foreach ($params->included as $key => $record) {
            $params->data->{$record->attributes->record_type}[] = $record->id;
        }

        return $params;
    }
}
