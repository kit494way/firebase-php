<?php

declare(strict_types=1);

namespace Kreait\Firebase;

use Kreait\Firebase\Exception\InvalidArgumentException;
use Kreait\Firebase\ServiceAccount\Discoverer;
use Kreait\Firebase\Util\JSON;
use Throwable;

class ServiceAccount
{
    /** @var array */
    private $data = [];

    /** @var string|null */
    private $filePath;

    /**
     * @return string|null
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getProjectId(): string
    {
        return $this->data['project_id'] ?? '';
    }

    /**
     * @deprecated 4.42.0
     * @codeCoverageIgnore
     */
    public function getSanitizedProjectId(): string
    {
        return \preg_replace('/[^A-Za-z0-9\-]/', '-', $this->getProjectId());
    }

    public function withProjectId(string $value): self
    {
        $serviceAccount = clone $this;
        $serviceAccount->data['project_id'] = $value;

        return $serviceAccount;
    }

    /**
     * @deprecated 4.42.0
     * @codeCoverageIgnore
     */
    public function hasClientId(): bool
    {
        return $this->getClientId() !== '';
    }

    public function getClientId(): string
    {
        return $this->data['client_id'] ?? '';
    }

    /**
     * @deprecated 4.42.0
     * @codeCoverageIgnore
     */
    public function withClientId(string $value): self
    {
        $serviceAccount = clone $this;
        $serviceAccount->data['client_id'] = $value;

        return $serviceAccount;
    }

    public function getClientEmail(): string
    {
        return $this->data['client_email'] ?? '';
    }

    /**
     * @deprecated 4.42.0
     * @codeCoverageIgnore
     */
    public function withClientEmail(string $value): self
    {
        if (!\filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid email.', $value));
        }

        $serviceAccount = clone $this;
        $serviceAccount->data['client_email'] = $value;

        return $serviceAccount;
    }

    /**
     * @deprecated 4.42.0
     * @codeCoverageIgnore
     */
    public function hasPrivateKey(): bool
    {
        return $this->getPrivateKey() !== '';
    }

    public function getPrivateKey(): string
    {
        return $this->data['private_key'] ?? '';
    }

    /**
     * @deprecated 4.42.0
     * @codeCoverageIgnore
     */
    public function withPrivateKey(string $value): self
    {
        $serviceAccount = clone $this;
        $serviceAccount->data['private_key'] = \str_replace('\n', "\n", $value);

        return $serviceAccount;
    }

    public function asArray(): array
    {
        $array = $this->data;
        $array['type'] = $array['type'] ?? 'service_account';

        return $array;
    }

    /**
     * @param mixed $value
     *
     * @throws InvalidArgumentException
     *
     * @return ServiceAccount
     */
    public static function fromValue($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (\is_string($value) && \mb_strpos($value, '{') === 0) {
            try {
                return self::fromJson($value);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException('Invalid service account specification');
            }
        }

        if (\is_string($value) && \mb_strpos($value, '{') !== 0) {
            try {
                return self::fromJsonFile($value);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException('Invalid service account specification');
            }
        }

        if (\is_array($value)) {
            return self::fromArray($value);
        }

        throw new InvalidArgumentException('Invalid service account specification.');
    }

    public static function fromArray(array $config): self
    {
        $requiredFields = ['project_id', 'client_id', 'client_email', 'private_key'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw new InvalidArgumentException(
                'The following fields are missing/empty in the Service Account specification: "'
                .\implode('", "', $missingFields)
                .'". Please make sure you download the Service Account JSON file from the Service Accounts tab '
                .'in the Firebase Console, as shown in the documentation on '
                .'https://firebase.google.com/docs/admin/setup#add_firebase_to_your_app'
            );
        }

        $serviceAccount = new self();
        $serviceAccount->data = $config;

        return $serviceAccount;
    }

    public static function fromJson(string $json): self
    {
        $config = JSON::decode($json, true);

        return self::fromArray($config);
    }

    public static function fromJsonFile(string $filePath): self
    {
        try {
            $file = new \SplFileObject($filePath);
            $json = $file->fread($file->getSize());
        } catch (Throwable $e) {
            throw new InvalidArgumentException("{$filePath} can not be read: {$e->getMessage()}");
        }

        if (!\is_string($json)) {
            throw new InvalidArgumentException("{$filePath} can not be read");
        }

        try {
            $serviceAccount = self::fromJson($json);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(\sprintf('%s could not be parsed to a Service Account: %s', $filePath, $e->getMessage()));
        }

        $serviceAccount->filePath = $filePath;

        return $serviceAccount;
    }

    public static function withProjectIdAndServiceAccountId(string $projectId, string $serviceAccountId): self
    {
        $serviceAccount = new self();
        $serviceAccount->data = [
            'project_id' => $projectId,
            'client_email' => $serviceAccountId,
        ];

        return $serviceAccount;
    }

    /**
     * @deprecated 4.42.0
     * @codeCoverageIgnore
     *
     * @return ServiceAccount
     */
    public static function discover(Discoverer $discoverer = null): self
    {
        $discoverer = $discoverer ?: new Discoverer();

        return $discoverer->discover();
    }
}
