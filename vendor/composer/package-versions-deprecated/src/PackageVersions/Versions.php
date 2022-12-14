<?php

declare(strict_types=1);

namespace PackageVersions;

use Composer\InstalledVersions;
use OutOfBoundsException;

class_exists(InstalledVersions::class);

/**
 * This class is generated by composer/package-versions-deprecated, specifically by
 * @see \PackageVersions\Installer
 *
 * This file is overwritten at every run of `composer install` or `composer update`.
 *
 * @deprecated in favor of the Composer\InstalledVersions class provided by Composer 2. Require composer-runtime-api:^2 to ensure it is present.
 */
final class Versions
{
    /**
     * @deprecated please use {@see self::rootPackageName()} instead.
     *             This constant will be removed in version 2.0.0.
     */
    const ROOT_PACKAGE_NAME = 'designtech/ic-airborne-api';

    /**
     * Array of all available composer packages.
     * Dont read this array from your calling code, but use the \PackageVersions\Versions::getVersion() method instead.
     *
     * @var array<string, string>
     * @internal
     */
    const VERSIONS          = array (
  'adldap2/adldap2' => 'v8.1.5@54722408c68f12942fcbf4a1b666d90a178ddc5c',
  'aws/aws-crt-php' => 'v1.0.2@3942776a8c99209908ee0b287746263725685732',
  'aws/aws-sdk-php' => '3.225.2@f846724ad842916061127d20da4fe4e129f7d4b8',
  'beberlei/doctrineextensions' => 'v1.3.0@008f162f191584a6c37c03a803f718802ba9dd9a',
  'bshaffer/oauth2-server-php' => 'v1.12.1@2bfaf9d7bbebe2ba1c1deb48e756ba0b3af4e985',
  'codeguy/upload' => 'dev-master@b32c8ae4e0e6e050696db9166a7dd57a186ef09a',
  'composer/package-versions-deprecated' => '1.11.99.5@b4f54f74ef3453349c24a845d22392cd31e65f1d',
  'doctrine/annotations' => '1.13.2@5b668aef16090008790395c02c893b1ba13f7e08',
  'doctrine/cache' => '1.13.0@56cd022adb5514472cb144c087393c1821911d09',
  'doctrine/collections' => '1.6.8@1958a744696c6bb3bb0d28db2611dc11610e78af',
  'doctrine/common' => '3.3.0@c824e95d4c83b7102d8bc60595445a6f7d540f96',
  'doctrine/dbal' => '2.13.9@c480849ca3ad6706a39c970cdfe6888fa8a058b8',
  'doctrine/deprecations' => 'v1.0.0@0e2a4f1f8cdfc7a92ec3b01c9334898c806b30de',
  'doctrine/event-manager' => '1.1.1@41370af6a30faa9dc0368c4a6814d596e81aba7f',
  'doctrine/inflector' => '1.4.4@4bd5c1cdfcd00e9e2d8c484f79150f67e5d355d9',
  'doctrine/instantiator' => '1.4.1@10dcfce151b967d20fde1b34ae6640712c3891bc',
  'doctrine/lexer' => '1.2.3@c268e882d4dbdd85e36e4ad69e02dc284f89d229',
  'doctrine/migrations' => 'v1.8.1@215438c0eef3e5f9b7da7d09c6b90756071b43e6',
  'doctrine/orm' => '2.7.4@7d84a4998091ece4d645253ac65de9f879eeed2f',
  'doctrine/persistence' => '2.5.3@d7edf274b6d35ad82328e223439cc2bb2f92bd9e',
  'guzzlehttp/guzzle' => '6.5.7@724562fa861e21a4071c652c8a159934e4f05592',
  'guzzlehttp/promises' => '1.5.1@fe752aedc9fd8fcca3fe7ad05d419d32998a06da',
  'guzzlehttp/psr7' => '1.8.5@337e3ad8e5716c15f9657bd214d16cc5e69df268',
  'illuminate/contracts' => 'v5.8.36@00fc6afee788fa07c311b0650ad276585f8aef96',
  'illuminate/support' => 'v5.8.36@df4af6a32908f1d89d74348624b57e3233eea247',
  'laminas/laminas-authentication' => '2.10.1@7308db03e11147fbf567b5004ac428bdaba267f9',
  'laminas/laminas-code' => '4.5.2@da01fb74c08f37e20e7ae49f1e3ee09aa401ebad',
  'laminas/laminas-eventmanager' => '3.5.0@41f7209428f37cab9573365e361f4078209aaafa',
  'laminas/laminas-filter' => '2.14.0@98a126b8cd069a446054680c9be5f37a61f6dc17',
  'laminas/laminas-hydrator' => '4.3.1@cc5ea6b42d318dbac872d94e8dca2d3013a37ab5',
  'laminas/laminas-json' => '3.3.0@9a0ce9f330b7d11e70c4acb44d67e8c4f03f437f',
  'laminas/laminas-permissions-acl' => '2.9.0@cd5689d8360c9a3f29bb62b32fc8ad45e0947e1e',
  'laminas/laminas-servicemanager' => '3.11.2@8a1f4d53ec93b2e18174f6f186922ef44d11a75a',
  'laminas/laminas-session' => '2.12.1@888c6a344e9a4c9f34ab6e09346640eac9be3fcf',
  'laminas/laminas-stdlib' => '3.10.1@0d669074845fc80a99add0f64025192f143ef836',
  'maennchen/zipstream-php' => '1.2.0@6373eefe0b3274d7b702d81f2c99aa977ff97dc2',
  'mandrill/mandrill' => '1.0.55@da3adc10042eafac2e53de141b358a52b8e53596',
  'monolog/monolog' => '1.27.1@904713c5929655dc9b97288b69cfeedad610c9a1',
  'mtdowling/jmespath.php' => '2.6.1@9b87907a81b87bc76d19a7fb2d61e61486ee9edb',
  'myclabs/php-enum' => '1.8.3@b942d263c641ddb5190929ff840c68f78713e937',
  'nesbot/carbon' => '2.58.0@97a34af22bde8d0ac20ab34b29d7bfe360902055',
  'nikic/fast-route' => 'v1.3.0@181d480e08d9476e61381e04a71b34dc0432e812',
  'ocramius/proxy-manager' => '2.13.1@e32bc1986fb6fa318ce35e573c654e3ddfb4848d',
  'paragonie/random_compat' => 'v2.0.21@96c132c7f2f7bc3230723b66e89f8f150b29d5ae',
  'phpcollection/phpcollection' => '0.4.0@b8bf55a0a929ca43b01232b36719f176f86c7e83',
  'phpoption/phpoption' => '1.8.1@eab7a0df01fe2344d172bff4cd6dbd3f8b84ad15',
  'phpseclib/mcrypt_compat' => '1.0.14@e38b76f02e6cf97aca05f5738eee1b917d922101',
  'phpseclib/phpseclib' => '2.0.37@c812fbb4d6b4d7f30235ab7298a12f09ba13b37c',
  'pimple/pimple' => 'v3.5.0@a94b3a4db7fb774b3d78dad2315ddc07629e1bed',
  'predis/predis' => 'v1.1.3@2ce537d75e610550f5337e41b2a971417999b028',
  'psr/cache' => '1.0.1@d11b50ad223250cf17b86e38383413f5a6764bf8',
  'psr/container' => '1.1.2@513e0666f7216c7459170d56df27dfcefe1689ea',
  'psr/http-message' => '1.0.1@f6561bf28d520154e4b0ec72be95418abe6d9363',
  'psr/log' => '1.1.4@d49695b909c3b7628b6289db5479a1c204601f11',
  'psr/simple-cache' => '1.0.1@408d5eafb83c57f6365a3ca330ff23aa4a5fa39b',
  'ralouphie/getallheaders' => '3.0.3@120b605dfeb996808c31b6477290a714d356e822',
  'ralouphie/mimey' => '2.1.0@8f74e6da73f9df7bd965e4e123f3d8fb9acb89ba',
  'ramsey/uuid' => '2.9.0@b2ef4dd9584268d73f92f752a62bc24cd534dc9a',
  'ruflin/elastica' => '2.3.3@29262657b33c003cf138b7c66f75e78bc5e01ddf',
  'slim/slim' => '3.12.3@1c9318a84ffb890900901136d620b4f03a59da38',
  'symfony/console' => 'v4.4.42@cce7a9f99e22937a71a16b23afa762558808d587',
  'symfony/deprecation-contracts' => 'v2.5.1@e8b495ea28c1d97b5e0c121748d6f9b53d075c66',
  'symfony/http-foundation' => 'v4.4.42@8e87b3ec23ebbcf7440d91dec8f7ca70dd591eb3',
  'symfony/mime' => 'v5.4.9@2b3802a24e48d0cfccf885173d2aac91e73df92e',
  'symfony/polyfill-intl-idn' => 'v1.26.0@59a8d271f00dd0e4c2e518104cc7963f655a1aa8',
  'symfony/polyfill-intl-normalizer' => 'v1.26.0@219aa369ceff116e673852dce47c3a41794c14bd',
  'symfony/polyfill-mbstring' => 'v1.26.0@9344f9cb97f3b19424af1a21a3b0e75b0a7d8d7e',
  'symfony/polyfill-php72' => 'v1.26.0@bf44a9fd41feaac72b074de600314a93e2ae78e2',
  'symfony/polyfill-php73' => 'v1.26.0@e440d35fa0286f77fb45b79a03fedbeda9307e85',
  'symfony/polyfill-php80' => 'v1.26.0@cfa0ae98841b9e461207c13ab093d76b0fa7bace',
  'symfony/service-contracts' => 'v2.5.1@24d9dc654b83e91aa59f9d167b131bc3b5bea24c',
  'symfony/translation' => 'v5.3.14@945066809dc18f6e26123098e1b6e1d7a948660b',
  'symfony/translation-contracts' => 'v2.5.1@1211df0afa701e45a04253110e959d4af4ef0f07',
  'webimpress/safe-writer' => '2.2.0@9d37cc8bee20f7cb2f58f6e23e05097eab5072e6',
  'webmozart/assert' => '1.11.0@11cb2199493b2f8a3b53e7f19068fc6aac760991',
  'yurevichcv/chargify-v2' => 'dev-master@77b236515be2b1f43e874d9cd1dff7d346671c29',
  'composer/ca-bundle' => '1.3.2@fd5dd441932a7e10ca6e5b490e272d34c8430640',
  'composer/composer' => '2.2.14@8c7a2d200bb0e66d6fafeff2f9c9a27188e52842',
  'composer/metadata-minifier' => '1.0.0@c549d23829536f0d0e984aaabbf02af91f443207',
  'composer/pcre' => '1.0.1@67a32d7d6f9f560b726ab25a061b38ff3a80c560',
  'composer/semver' => '3.3.2@3953f23262f2bff1919fc82183ad9acb13ff62c9',
  'composer/spdx-licenses' => '1.5.7@c848241796da2abf65837d51dce1fae55a960149',
  'composer/xdebug-handler' => '3.0.3@ced299686f41dce890debac69273b47ffe98a40c',
  'firephp/firephp-core' => 'v0.4.0@fabad0f2503f9577fe8dd2cb1d1c7cd73ed2aacf',
  'heroku/heroku-buildpack-php' => 'v219@9acc2e638f02b7249b51225acb03fb7a7259f447',
  'justinrainbow/json-schema' => '5.2.12@ad87d5a5ca981228e0e205c2bc7dfb8e24559b60',
  'myclabs/deep-copy' => '1.11.0@14daed4296fae74d9e3201d2c4925d1acb7aa614',
  'pdepend/pdepend' => '2.10.3@da3166a06b4a89915920a42444f707122a1584c9',
  'phpdocumentor/reflection-common' => '2.2.0@1d01c49d4ed62f25aa84a747ad35d5a16924662b',
  'phpdocumentor/reflection-docblock' => '5.3.0@622548b623e81ca6d78b721c5e029f4ce664f170',
  'phpdocumentor/type-resolver' => '1.6.1@77a32518733312af16a44300404e945338981de3',
  'phploc/phploc' => '5.0.0@5b714ccb7cb8ca29ccf9caf6eb1aed0131d3a884',
  'phpmd/phpmd' => '2.12.0@c0b678ba71902f539c27c14332aa0ddcf14388ec',
  'phpspec/prophecy' => 'v1.10.3@451c3cd1418cf640de218914901e51b064abb093',
  'phpunit/dbunit' => '1.3.1@a5891b7a9c4f21587a51f9bc4e8f7042b741b480',
  'phpunit/php-code-coverage' => '4.0.8@ef7b2f56815df854e66ceaee8ebe9393ae36a40d',
  'phpunit/php-file-iterator' => '1.4.5@730b01bc3e867237eaac355e06a36b85dd93a8b4',
  'phpunit/php-text-template' => '1.2.1@31f8b717e51d9a2afca6c9f046f5d69fc27c8686',
  'phpunit/php-timer' => '1.0.9@3dcf38ca72b158baf0bc245e9184d3fdffa9c46f',
  'phpunit/php-token-stream' => '2.0.2@791198a2c6254db10131eecfe8c06670700904db',
  'phpunit/phpunit' => '5.7.27@b7803aeca3ccb99ad0a506fa80b64cd6a56bbc0c',
  'phpunit/phpunit-mock-objects' => '3.4.4@a23b761686d50a560cc56233b9ecf49597cc9118',
  'react/promise' => 'v2.9.0@234f8fd1023c9158e2314fa9d7d0e6a83db42910',
  'sebastian/code-unit-reverse-lookup' => '1.0.2@1de8cd5c010cb153fcd68b8d0f64606f523f7619',
  'sebastian/comparator' => '1.2.4@2b7424b55f5047b47ac6e5ccb20b2aea4011d9be',
  'sebastian/diff' => '1.4.3@7f066a26a962dbe58ddea9f72a4e82874a3975a4',
  'sebastian/environment' => '2.0.0@5795ffe5dc5b02460c3e34222fee8cbe245d8fac',
  'sebastian/exporter' => '2.0.0@ce474bdd1a34744d7ac5d6aad3a46d48d9bac4c4',
  'sebastian/finder-facade' => '1.2.3@167c45d131f7fc3d159f56f191a0a22228765e16',
  'sebastian/global-state' => '1.1.1@bc37d50fea7d017d3d340f230811c9f1d7280af4',
  'sebastian/object-enumerator' => '2.0.1@1311872ac850040a79c3c058bea3e22d0f09cbb7',
  'sebastian/phpcpd' => '3.0.1@dfed51c1288790fc957c9433e2f49ab152e8a564',
  'sebastian/recursion-context' => '2.0.0@2c3ba150cbec723aa057506e73a8d33bdb286c9a',
  'sebastian/resource-operations' => '1.0.0@ce990bb21759f94aeafd30209e8cfcdfa8bc3f52',
  'sebastian/version' => '2.0.1@99732be0ddb3361e16ad77b68ba41efc8e979019',
  'seld/jsonlint' => '1.9.0@4211420d25eba80712bff236a98960ef68b866b7',
  'seld/phar-utils' => '1.2.0@9f3452c93ff423469c0d56450431562ca423dcee',
  'squizlabs/php_codesniffer' => '3.7.0@a2cd51b45bcaef9c1f2a4bda48f2dd2fa2b95563',
  'symfony/config' => 'v5.4.9@8f551fe22672ac7ab2c95fe46d899f960ed4d979',
  'symfony/dependency-injection' => 'v5.4.9@beecae161577305926ec078c4ed973f2b98880b3',
  'symfony/filesystem' => 'v5.4.9@36a017fa4cce1eff1b8e8129ff53513abcef05ba',
  'symfony/finder' => 'v5.4.8@9b630f3427f3ebe7cd346c277a1408b00249dad9',
  'symfony/polyfill-ctype' => 'v1.26.0@6fd1b9a79f6e3cf65f9e679b23af304cd9e010d4',
  'symfony/polyfill-php81' => 'v1.26.0@13f6d1271c663dc5ae9fb843a8f16521db7687a1',
  'symfony/process' => 'v5.4.8@597f3fff8e3e91836bb0bd38f5718b56ddbde2f3',
  'symfony/yaml' => 'v4.4.37@d7f637cc0f0cc14beb0984f2bb50da560b271311',
  'theseer/fdomdocument' => '1.6.7@5cddd4f9076a9a2b85c5135935bba2dcb3ed7574',
  'designtech/ic-airborne-api' => '2.8.5@',
);

    private function __construct()
    {
    }

    /**
     * @psalm-pure
     *
     * @psalm-suppress ImpureMethodCall we know that {@see InstalledVersions} interaction does not
     *                                  cause any side effects here.
     */
    public static function rootPackageName() : string
    {
        if (!self::composer2ApiUsable()) {
            return self::ROOT_PACKAGE_NAME;
        }

        return InstalledVersions::getRootPackage()['name'];
    }

    /**
     * @throws OutOfBoundsException If a version cannot be located.
     *
     * @psalm-param key-of<self::VERSIONS> $packageName
     * @psalm-pure
     *
     * @psalm-suppress ImpureMethodCall we know that {@see InstalledVersions} interaction does not
     *                                  cause any side effects here.
     */
    public static function getVersion(string $packageName): string
    {
        if (self::composer2ApiUsable()) {
            return InstalledVersions::getPrettyVersion($packageName)
                . '@' . InstalledVersions::getReference($packageName);
        }

        if (isset(self::VERSIONS[$packageName])) {
            return self::VERSIONS[$packageName];
        }

        throw new OutOfBoundsException(
            'Required package "' . $packageName . '" is not installed: check your ./vendor/composer/installed.json and/or ./composer.lock files'
        );
    }

    private static function composer2ApiUsable(): bool
    {
        if (!class_exists(InstalledVersions::class, false)) {
            return false;
        }

        if (method_exists(InstalledVersions::class, 'getAllRawData')) {
            $rawData = InstalledVersions::getAllRawData();
            if (count($rawData) === 1 && count($rawData[0]) === 0) {
                return false;
            }
        } else {
            $rawData = InstalledVersions::getRawData();
            if ($rawData === null || $rawData === []) {
                return false;
            }
        }

        return true;
    }
}
