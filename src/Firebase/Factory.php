<?php

declare(strict_types=1);

namespace Kreait\Firebase;

use Firebase\Auth\Token\Cache\InMemoryCache;
use Firebase\Auth\Token\Domain\Generator as LegacyCustomTokenGeneratorContract;
use Firebase\Auth\Token\Domain\Verifier as LegacyIdTokenVerifierContract;
use Firebase\Auth\Token\Generator as LegacyCustomTokenGenerator;
use Firebase\Auth\Token\Verifier as LegacyIdTokenVerifier;
use Firebase\Auth\Token\HttpKeyStore;
use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\CredentialsLoader;
use Google\Auth\Middleware\AuthTokenMiddleware;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Storage\StorageClient;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kreait\Firebase\Auth\IdTokenVerifier;
use Kreait\Firebase\Auth\NonFunctionalLegacyCustomTokenGenerator;
use Kreait\Firebase\Auth\NonFunctionalLegacyIdTokenVerifier;
use Kreait\Firebase\Value\ProjectId;
use function GuzzleHttp\Psr7\uri_for;
use Kreait\Clock;
use Kreait\Clock\SystemClock;
use Kreait\Firebase;
use Kreait\Firebase\Auth\CustomTokenViaGoogleIam;
use Kreait\Firebase\Exception\LogicException;
use Kreait\Firebase\Exception\RuntimeException;
use Kreait\Firebase\Http\Middleware;
use Kreait\Firebase\Value\Url;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

class Factory
{
    const API_CLIENT_SCOPES = [
        'https://www.googleapis.com/auth/iam',
        'https://www.googleapis.com/auth/cloud-platform',
        'https://www.googleapis.com/auth/firebase',
        'https://www.googleapis.com/auth/firebase.database',
        'https://www.googleapis.com/auth/firebase.messaging',
        'https://www.googleapis.com/auth/firebase.remoteconfig',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/securetoken',
    ];

    /**
     * @var ProjectId|null
     */
    protected $projectId;

    /**
     * @var UriInterface|null
     */
    protected $databaseUri;

    /**
     * @var string|null
     */
    protected $defaultStorageBucket;

    /**
     * @var ServiceAccount|null
     */
    protected $serviceAccount;

    /**
     * @var string|null
     */
    protected $uid;

    /**
     * @var array
     */
    protected $claims = [];

    /**
     * @var CacheInterface|null
     */
    protected $verifierCache;

    /**
     * @var array
     */
    protected $httpClientConfig = [];

    /**
     * @var array
     */
    protected $httpClientMiddlewares = [];

    /**
     * @var bool
     */
    protected $discoveryIsDisabled = false;

    protected static $databaseUriPattern = 'https://%s.firebaseio.com';

    protected static $storageBucketNamePattern = '%s.appspot.com';

    /** @var Clock */
    protected $clock;

    public function __construct()
    {
        $this->clock = new SystemClock();
    }

    public function withServiceAccount($serviceAccount): self
    {
        $serviceAccount = ServiceAccount::fromValue($serviceAccount);

        $factory = clone $this;
        $factory->serviceAccount = $serviceAccount;

        return $factory
            ->withProjectId($serviceAccount->getProjectId())
            ->withDisabledAutoDiscovery();
    }

    public function withProjectId(string $projectId): self
    {
        $factory = clone $this;
        $factory->projectId = new ProjectId($projectId);

        return $factory;
    }

    public function withSuppressedWarnings(): self
    {
        putenv('SUPPRESS_GCLOUD_CREDS_WARNING=true');

        return $this;
    }

    /**
     * @deprecated 4.42.0 This method has no effect anymore
     */
    public function withServiceAccountDiscoverer(): self
    {
        return $this;
    }

    public function withDisabledAutoDiscovery(): self
    {
        $factory = clone $this;
        $factory->discoveryIsDisabled = true;

        return $factory;
    }

    public function withDatabaseUri($uri): self
    {
        $factory = clone $this;
        $factory->databaseUri = uri_for($uri);

        return $factory;
    }

    public function withDefaultStorageBucket($name): self
    {
        $factory = clone $this;
        $factory->defaultStorageBucket = $name;

        return $factory;
    }

    public function withVerifierCache(CacheInterface $cache): self
    {
        $factory = clone $this;
        $factory->verifierCache = $cache;

        return $factory;
    }

    public function withHttpClientConfig(array $config = null): self
    {
        $factory = clone $this;
        $factory->httpClientConfig = $config ?? [];

        return $factory;
    }

    /**
     * @param callable[]|null $middlewares
     */
    public function withHttpClientMiddlewares(array $middlewares = null): self
    {
        $factory = clone $this;
        $factory->httpClientMiddlewares = $middlewares ?? [];

        return $factory;
    }

    public function withClock(Clock $clock): self
    {
        $factory = clone $this;
        $factory->clock = $clock;

        return $factory;
    }

    /**
     * @deprecated 4.41
     * @codeCoverageIgnore
     */
    public function asUser(string $uid, array $claims = null): self
    {
        $factory = clone $this;
        $factory->uid = $uid;
        $factory->claims = $claims ?? [];

        return $factory;
    }

    /**
     * @deprecated 4.33 Use the component-specific create*() methods instead.
     * @see createAuth()
     * @see createDatabase()
     * @see createFirestore()
     * @see createMessaging()
     * @see createRemoteConfig()
     * @see createStorage()
     */
    public function create(): Firebase
    {
        /* @noinspection PhpInternalEntityUsedInspection */
        return new Firebase($this);
    }

    /**
     * @return ServiceAccount|null
     */
    protected function getServiceAccount()
    {
        if ($this->serviceAccount) {
            return $this->serviceAccount;
        }

        if ($credentials = getenv('FIREBASE_CREDENTIALS')) {
            return $this->serviceAccount = ServiceAccount::fromValue($credentials);
        }

        return null;
    }

    /**
     * @return ProjectId|null
     */
    protected function getProjectId()
    {
        if ($this->projectId) {
            return $this->projectId;
        }

        if ($serviceAccount = $this->getServiceAccount()) {
            return $this->projectId = new ProjectId($serviceAccount->getProjectId());
        }

        return null;
    }

    protected function getDatabaseUri(): UriInterface
    {
        if ($this->databaseUri) {
            return $this->databaseUri;
        }

        if ($projectId = $this->getProjectId()) {
            return uri_for(\sprintf(self::$databaseUriPattern, $projectId->sanitizedValue()));
        }

        throw new LogicException('Unable to determine database URI.');
    }

    protected function getStorageBucketName(): string
    {
        if ($this->defaultStorageBucket) {
            return $this->defaultStorageBucket;
        }

        if ($projectId = $this->getProjectId()) {
            return \sprintf(self::$storageBucketNamePattern, $projectId->sanitizedValue());
        }

        throw new LogicException('Unable to determine storage bucket name.');
    }

    public function createAuth(): Auth
    {
        $http = $this->createApiClient([
            'base_uri' => 'https://www.googleapis.com/identitytoolkit/v3/relyingparty/',
        ]);
        $apiClient = new Auth\ApiClient($http);


        $customTokenGenerator = $this->createCustomTokenGenerator();
        $idTokenVerifier = $this->createIdTokenVerifier();

        $signInHandler = new Firebase\Auth\SignIn\GuzzleHandler($http);

        return new Auth($apiClient, $customTokenGenerator, $idTokenVerifier, $signInHandler);
    }

    public function createCustomTokenGenerator(): LegacyCustomTokenGeneratorContract
    {
        if ($serviceAccount = $this->getServiceAccount()) {
            $privateKey = $serviceAccount->getPrivateKey();
            $clientEmail = $serviceAccount->getClientEmail();

            if ($privateKey !== '' && $clientEmail !== '') {
                return new LegacyCustomTokenGenerator($serviceAccount->getClientEmail(), $serviceAccount->getPrivateKey());
            }

            if ($clientEmail !== '') {
                return new CustomTokenViaGoogleIam($clientEmail, $this->createApiClient());
            }
        }

        return new NonFunctionalLegacyCustomTokenGenerator(
            "The provided authentication credentials don't allow the generation of custom tokens"
        );
    }

    protected function createLegacyIdTokenVerifier(): LegacyIdTokenVerifierContract
    {
        if ($projectId = $this->getProjectId()) {
            $keyStore = new HttpKeyStore(new Client(), $this->verifierCache ?: new InMemoryCache());
            return new LegacyIdTokenVerifier($projectId->sanitizedValue(), $keyStore);
        }

        return new NonFunctionalLegacyIdTokenVerifier(
            "The provided authentication credentials don't allow the verification of ID tokens"
        );
    }

    public function createIdTokenVerifier(): LegacyIdTokenVerifierContract
    {
        $baseVerifier = $this->createLegacyIdTokenVerifier();

        return new IdTokenVerifier($baseVerifier, $this->clock);
    }

    public function createDatabase(): Database
    {
        $http = $this->createApiClient();

        $middlewares = [
            'json_suffix' => Firebase\Http\Middleware::ensureJsonSuffix(),
        ];

        if ($this->uid) {
            $authOverride = new Http\Auth\CustomToken($this->uid, $this->claims);

            $middlewares['auth_override'] = Middleware::overrideAuth($authOverride);
        }

        /** @var HandlerStack $handler */
        $handler = $http->getConfig('handler');

        foreach ($middlewares as $name => $middleware) {
            $handler->push($middleware, $name);
        }

        return new Database($this->getDatabaseUri(), new Database\ApiClient($http));
    }

    public function createRemoteConfig(): RemoteConfig
    {
        $projectId = $this->getProjectId();

        $http = $this->createApiClient([
            'base_uri' => "https://firebaseremoteconfig.googleapis.com/v1/projects/{$projectId}/remoteConfig",
        ]);

        return new RemoteConfig(new RemoteConfig\ApiClient($http));
    }

    public function createMessaging(): Messaging
    {
        $projectId = $this->getProjectId();

        $messagingApiClient = new Messaging\ApiClient(
            $this->createApiClient([
                'base_uri' => 'https://fcm.googleapis.com/v1/projects/'.$projectId,
            ])
        );

        $appInstanceApiClient = new Messaging\AppInstanceApiClient(
            $this->createApiClient([
                'base_uri' => 'https://iid.googleapis.com',
                'headers' => [
                    'access_token_auth' => 'true',
                ],
            ])
        );

        return new Messaging($messagingApiClient, $appInstanceApiClient, $projectId);
    }

    /**
     * @param string|Url|UriInterface|mixed $defaultDynamicLinksDomain
     */
    public function createDynamicLinksService($defaultDynamicLinksDomain = null): DynamicLinks
    {
        $apiClient = $this->createApiClient();

        if ($defaultDynamicLinksDomain) {
            return DynamicLinks::withApiClientAndDefaultDomain($apiClient, $defaultDynamicLinksDomain);
        }

        return DynamicLinks::withApiClient($apiClient);
    }

    public function createFirestore(array $firestoreClientConfig = null): Firestore
    {
        $client = $this->createFirestoreClient($firestoreClientConfig);

        return Firestore::withFirestoreClient($client);
    }

    private function createFirestoreClient(array $config = null): FirestoreClient
    {
        $config = $config ?: [];
        $config = \array_merge($this->googleClientAuthConfig(), $config);

        try {
            return new FirestoreClient($config);
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to create a FirestoreClient: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createStorage(array $storageClientConfig = null): Storage
    {
        $storageClientConfig = $storageClientConfig ?: [];

        $client = $this->createStorageClient($storageClientConfig);

        return new Storage($client, $this->getStorageBucketName());
    }

    private function createStorageClient(array $config = null): StorageClient
    {
        $config = $config ?: [];
        $config = \array_merge($this->googleClientAuthConfig(), $config);

        try {
            return new StorageClient($config);
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to create a StorageClient: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createApiClient(array $config = null): Client
    {
        $config = $config ?? [];
        // If present, the config given to this method override fields passed to withHttpClientConfig()
        $config = \array_merge($this->httpClientConfig, $config);

        $handler = $config['handler'] ?? null;

        if (!($handler instanceof HandlerStack)) {
            $handler = HandlerStack::create($handler);
        }

        foreach ($this->httpClientMiddlewares as $middleware) {
            $handler->push($middleware);
        }

        $handler->push($this->createGoogleAuthTokenMiddleware());
        $handler->push(Middleware::responseWithSubResponses());

        $config['handler'] = $handler;
        $config['auth'] = 'google_auth';

        return new Client($config);
    }

    protected function createGoogleAuthTokenMiddleware(): AuthTokenMiddleware
    {
        if ($serviceAccount = $this->getServiceAccount()) {
            return new AuthTokenMiddleware(
                CredentialsLoader::makeCredentials(self::API_CLIENT_SCOPES, $serviceAccount->asArray())
            );
        }

        if (!$this->discoveryIsDisabled) {
            return ApplicationDefaultCredentials::getMiddleware(self::API_CLIENT_SCOPES);
        }

        throw new LogicException('Unable to retrieve credentials.');
    }

    protected function googleClientAuthConfig(): array
    {
        if ($serviceAccount = $this->getServiceAccount()) {
            if ($filePath = $serviceAccount->getFilePath()) {
                return [
                    'keyFilePath' => $filePath,
                ];
            }

            $clientId = $serviceAccount->getClientId();
            $privateKey = $serviceAccount->getPrivateKey();

            if ($clientId !== '' && $privateKey !== '') {
                return [
                    'keyFile' => [
                        'client_email' => $serviceAccount->getClientEmail(),
                        'client_id' => $serviceAccount->getClientId(),
                        'private_key' => $serviceAccount->getPrivateKey(),
                        'project_id' => $serviceAccount->getProjectId(),
                        'type' => 'service_account',
                    ],
                ];
            }
        }

        if ($this->discoveryIsDisabled) {
            throw new LogicException('Unable to retrieve credentials.');
        }

        return [];
    }
}
