<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Constants\ConfigurationKey;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Configuration\Contracts\MailConfigurationResolverInterface;
use Modules\Configuration\Data\MailConfiguration;

final class MailConfigurationResolver implements MailConfigurationResolverInterface
{
    public function __construct(
        private readonly ConfigurationResolverInterface $resolver,
    ) {}

    public function resolve(int $tenantId, ?int $organizationUnitId = null): MailConfiguration
    {
        $mailer = (string) $this->resolver->value(ConfigurationKey::MAIL_MAILER, $tenantId, $organizationUnitId);
        $host = (string) $this->resolver->value(ConfigurationKey::MAIL_HOST, $tenantId, $organizationUnitId);
        $port = (int) $this->resolver->value(ConfigurationKey::MAIL_PORT, $tenantId, $organizationUnitId);
        $username = $this->resolver->value(ConfigurationKey::MAIL_USERNAME, $tenantId, $organizationUnitId);
        $password = $this->resolver->value(ConfigurationKey::MAIL_PASSWORD, $tenantId, $organizationUnitId);
        $encryption = $this->resolver->value(ConfigurationKey::MAIL_ENCRYPTION, $tenantId, $organizationUnitId);
        $fromAddress = (string) $this->resolver->value(ConfigurationKey::MAIL_FROM_ADDRESS, $tenantId, $organizationUnitId);
        $fromName = (string) $this->resolver->value(ConfigurationKey::MAIL_FROM_NAME, $tenantId, $organizationUnitId);

        return new MailConfiguration(
            mailer: $mailer,
            host: $host,
            port: $port,
            username: $username !== null ? (string) $username : null,
            password: $password !== null ? (string) $password : null,
            encryption: $encryption !== null ? (string) $encryption : null,
            fromAddress: $fromAddress,
            fromName: $fromName,
        );
    }

    public function configureEnvironment(int $tenantId, ?int $organizationUnitId = null): MailConfiguration
    {
        $mail = $this->resolve($tenantId, $organizationUnitId);

        config([
            'mail.default' => $mail->mailer,
            'mail.mailers.smtp.host' => $mail->host,
            'mail.mailers.smtp.port' => $mail->port,
            'mail.mailers.smtp.username' => $mail->username,
            'mail.mailers.smtp.password' => $mail->password,
            'mail.mailers.smtp.scheme' => $mail->encryption === 'none' ? null : $mail->encryption,
            'mail.from.address' => $mail->fromAddress,
            'mail.from.name' => $mail->fromName,
        ]);

        return $mail;
    }
}
