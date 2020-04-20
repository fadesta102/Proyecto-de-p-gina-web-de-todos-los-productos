<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0e0f84c502efc5e060d5602a9841af8f
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        'a0edc8309cc5e1d60e3047b5df6b7052' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/functions_include.php',
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
        ),
        'M' => 
        array (
            'Mollie\\Api\\' => 11,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
        ),
        'C' => 
        array (
            'Composer\\CaBundle\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'Mollie\\Api\\' => 
        array (
            0 => __DIR__ . '/..' . '/mollie/mollie-api-php/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'Composer\\CaBundle\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/ca-bundle/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'M' => 
        array (
            'Mollie_WC_' => 
            array (
                0 => __DIR__ . '/../..' . '/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\CaBundle\\CaBundle' => __DIR__ . '/..' . '/composer/ca-bundle/src/CaBundle.php',
        'GuzzleHttp\\Client' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Client.php',
        'GuzzleHttp\\ClientInterface' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/ClientInterface.php',
        'GuzzleHttp\\Cookie\\CookieJar' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Cookie/CookieJar.php',
        'GuzzleHttp\\Cookie\\CookieJarInterface' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Cookie/CookieJarInterface.php',
        'GuzzleHttp\\Cookie\\FileCookieJar' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Cookie/FileCookieJar.php',
        'GuzzleHttp\\Cookie\\SessionCookieJar' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Cookie/SessionCookieJar.php',
        'GuzzleHttp\\Cookie\\SetCookie' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Cookie/SetCookie.php',
        'GuzzleHttp\\Exception\\BadResponseException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/BadResponseException.php',
        'GuzzleHttp\\Exception\\ClientException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/ClientException.php',
        'GuzzleHttp\\Exception\\ConnectException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/ConnectException.php',
        'GuzzleHttp\\Exception\\GuzzleException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/GuzzleException.php',
        'GuzzleHttp\\Exception\\InvalidArgumentException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/InvalidArgumentException.php',
        'GuzzleHttp\\Exception\\RequestException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/RequestException.php',
        'GuzzleHttp\\Exception\\SeekException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/SeekException.php',
        'GuzzleHttp\\Exception\\ServerException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/ServerException.php',
        'GuzzleHttp\\Exception\\TooManyRedirectsException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/TooManyRedirectsException.php',
        'GuzzleHttp\\Exception\\TransferException' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Exception/TransferException.php',
        'GuzzleHttp\\HandlerStack' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/HandlerStack.php',
        'GuzzleHttp\\Handler\\CurlFactory' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Handler/CurlFactory.php',
        'GuzzleHttp\\Handler\\CurlFactoryInterface' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Handler/CurlFactoryInterface.php',
        'GuzzleHttp\\Handler\\CurlHandler' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Handler/CurlHandler.php',
        'GuzzleHttp\\Handler\\CurlMultiHandler' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Handler/CurlMultiHandler.php',
        'GuzzleHttp\\Handler\\EasyHandle' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Handler/EasyHandle.php',
        'GuzzleHttp\\Handler\\MockHandler' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Handler/MockHandler.php',
        'GuzzleHttp\\Handler\\Proxy' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Handler/Proxy.php',
        'GuzzleHttp\\Handler\\StreamHandler' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Handler/StreamHandler.php',
        'GuzzleHttp\\MessageFormatter' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/MessageFormatter.php',
        'GuzzleHttp\\Middleware' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Middleware.php',
        'GuzzleHttp\\Pool' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/Pool.php',
        'GuzzleHttp\\PrepareBodyMiddleware' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/PrepareBodyMiddleware.php',
        'GuzzleHttp\\Promise\\AggregateException' => __DIR__ . '/..' . '/guzzlehttp/promises/src/AggregateException.php',
        'GuzzleHttp\\Promise\\CancellationException' => __DIR__ . '/..' . '/guzzlehttp/promises/src/CancellationException.php',
        'GuzzleHttp\\Promise\\Coroutine' => __DIR__ . '/..' . '/guzzlehttp/promises/src/Coroutine.php',
        'GuzzleHttp\\Promise\\EachPromise' => __DIR__ . '/..' . '/guzzlehttp/promises/src/EachPromise.php',
        'GuzzleHttp\\Promise\\FulfilledPromise' => __DIR__ . '/..' . '/guzzlehttp/promises/src/FulfilledPromise.php',
        'GuzzleHttp\\Promise\\Promise' => __DIR__ . '/..' . '/guzzlehttp/promises/src/Promise.php',
        'GuzzleHttp\\Promise\\PromiseInterface' => __DIR__ . '/..' . '/guzzlehttp/promises/src/PromiseInterface.php',
        'GuzzleHttp\\Promise\\PromisorInterface' => __DIR__ . '/..' . '/guzzlehttp/promises/src/PromisorInterface.php',
        'GuzzleHttp\\Promise\\RejectedPromise' => __DIR__ . '/..' . '/guzzlehttp/promises/src/RejectedPromise.php',
        'GuzzleHttp\\Promise\\RejectionException' => __DIR__ . '/..' . '/guzzlehttp/promises/src/RejectionException.php',
        'GuzzleHttp\\Promise\\TaskQueue' => __DIR__ . '/..' . '/guzzlehttp/promises/src/TaskQueue.php',
        'GuzzleHttp\\Promise\\TaskQueueInterface' => __DIR__ . '/..' . '/guzzlehttp/promises/src/TaskQueueInterface.php',
        'GuzzleHttp\\Psr7\\AppendStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/AppendStream.php',
        'GuzzleHttp\\Psr7\\BufferStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/BufferStream.php',
        'GuzzleHttp\\Psr7\\CachingStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/CachingStream.php',
        'GuzzleHttp\\Psr7\\DroppingStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/DroppingStream.php',
        'GuzzleHttp\\Psr7\\FnStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/FnStream.php',
        'GuzzleHttp\\Psr7\\InflateStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/InflateStream.php',
        'GuzzleHttp\\Psr7\\LazyOpenStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/LazyOpenStream.php',
        'GuzzleHttp\\Psr7\\LimitStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/LimitStream.php',
        'GuzzleHttp\\Psr7\\MessageTrait' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/MessageTrait.php',
        'GuzzleHttp\\Psr7\\MultipartStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/MultipartStream.php',
        'GuzzleHttp\\Psr7\\NoSeekStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/NoSeekStream.php',
        'GuzzleHttp\\Psr7\\PumpStream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/PumpStream.php',
        'GuzzleHttp\\Psr7\\Request' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/Request.php',
        'GuzzleHttp\\Psr7\\Response' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/Response.php',
        'GuzzleHttp\\Psr7\\Rfc7230' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/Rfc7230.php',
        'GuzzleHttp\\Psr7\\ServerRequest' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/ServerRequest.php',
        'GuzzleHttp\\Psr7\\Stream' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/Stream.php',
        'GuzzleHttp\\Psr7\\StreamDecoratorTrait' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/StreamDecoratorTrait.php',
        'GuzzleHttp\\Psr7\\StreamWrapper' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/StreamWrapper.php',
        'GuzzleHttp\\Psr7\\UploadedFile' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/UploadedFile.php',
        'GuzzleHttp\\Psr7\\Uri' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/Uri.php',
        'GuzzleHttp\\Psr7\\UriNormalizer' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/UriNormalizer.php',
        'GuzzleHttp\\Psr7\\UriResolver' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/UriResolver.php',
        'GuzzleHttp\\RedirectMiddleware' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/RedirectMiddleware.php',
        'GuzzleHttp\\RequestOptions' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/RequestOptions.php',
        'GuzzleHttp\\RetryMiddleware' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/RetryMiddleware.php',
        'GuzzleHttp\\TransferStats' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/TransferStats.php',
        'GuzzleHttp\\UriTemplate' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/UriTemplate.php',
        'Mollie\\Api\\CompatibilityChecker' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/CompatibilityChecker.php',
        'Mollie\\Api\\Endpoints\\ChargebackEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/ChargebackEndpoint.php',
        'Mollie\\Api\\Endpoints\\CollectionEndpointAbstract' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/CollectionEndpointAbstract.php',
        'Mollie\\Api\\Endpoints\\CustomerEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/CustomerEndpoint.php',
        'Mollie\\Api\\Endpoints\\CustomerPaymentsEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/CustomerPaymentsEndpoint.php',
        'Mollie\\Api\\Endpoints\\EndpointAbstract' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/EndpointAbstract.php',
        'Mollie\\Api\\Endpoints\\InvoiceEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/InvoiceEndpoint.php',
        'Mollie\\Api\\Endpoints\\MandateEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/MandateEndpoint.php',
        'Mollie\\Api\\Endpoints\\MethodEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/MethodEndpoint.php',
        'Mollie\\Api\\Endpoints\\OnboardingEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/OnboardingEndpoint.php',
        'Mollie\\Api\\Endpoints\\OrderEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/OrderEndpoint.php',
        'Mollie\\Api\\Endpoints\\OrderLineEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/OrderLineEndpoint.php',
        'Mollie\\Api\\Endpoints\\OrderPaymentEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/OrderPaymentEndpoint.php',
        'Mollie\\Api\\Endpoints\\OrderRefundEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/OrderRefundEndpoint.php',
        'Mollie\\Api\\Endpoints\\OrganizationEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/OrganizationEndpoint.php',
        'Mollie\\Api\\Endpoints\\PaymentCaptureEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/PaymentCaptureEndpoint.php',
        'Mollie\\Api\\Endpoints\\PaymentChargebackEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/PaymentChargebackEndpoint.php',
        'Mollie\\Api\\Endpoints\\PaymentEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/PaymentEndpoint.php',
        'Mollie\\Api\\Endpoints\\PaymentRefundEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/PaymentRefundEndpoint.php',
        'Mollie\\Api\\Endpoints\\PermissionEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/PermissionEndpoint.php',
        'Mollie\\Api\\Endpoints\\ProfileEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/ProfileEndpoint.php',
        'Mollie\\Api\\Endpoints\\ProfileMethodEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/ProfileMethodEndpoint.php',
        'Mollie\\Api\\Endpoints\\RefundEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/RefundEndpoint.php',
        'Mollie\\Api\\Endpoints\\SettlementsEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/SettlementsEndpoint.php',
        'Mollie\\Api\\Endpoints\\ShipmentEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/ShipmentEndpoint.php',
        'Mollie\\Api\\Endpoints\\SubscriptionEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/SubscriptionEndpoint.php',
        'Mollie\\Api\\Endpoints\\WalletEndpoint' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Endpoints/WalletEndpoint.php',
        'Mollie\\Api\\Exceptions\\ApiException' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Exceptions/ApiException.php',
        'Mollie\\Api\\Exceptions\\IncompatiblePlatform' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Exceptions/IncompatiblePlatform.php',
        'Mollie\\Api\\MollieApiClient' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/MollieApiClient.php',
        'Mollie\\Api\\Resources\\BaseCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/BaseCollection.php',
        'Mollie\\Api\\Resources\\BaseResource' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/BaseResource.php',
        'Mollie\\Api\\Resources\\Capture' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Capture.php',
        'Mollie\\Api\\Resources\\CaptureCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/CaptureCollection.php',
        'Mollie\\Api\\Resources\\Chargeback' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Chargeback.php',
        'Mollie\\Api\\Resources\\ChargebackCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/ChargebackCollection.php',
        'Mollie\\Api\\Resources\\CurrentProfile' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/CurrentProfile.php',
        'Mollie\\Api\\Resources\\CursorCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/CursorCollection.php',
        'Mollie\\Api\\Resources\\Customer' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Customer.php',
        'Mollie\\Api\\Resources\\CustomerCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/CustomerCollection.php',
        'Mollie\\Api\\Resources\\Invoice' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Invoice.php',
        'Mollie\\Api\\Resources\\InvoiceCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/InvoiceCollection.php',
        'Mollie\\Api\\Resources\\Issuer' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Issuer.php',
        'Mollie\\Api\\Resources\\IssuerCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/IssuerCollection.php',
        'Mollie\\Api\\Resources\\Mandate' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Mandate.php',
        'Mollie\\Api\\Resources\\MandateCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/MandateCollection.php',
        'Mollie\\Api\\Resources\\Method' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Method.php',
        'Mollie\\Api\\Resources\\MethodCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/MethodCollection.php',
        'Mollie\\Api\\Resources\\MethodPrice' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/MethodPrice.php',
        'Mollie\\Api\\Resources\\MethodPriceCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/MethodPriceCollection.php',
        'Mollie\\Api\\Resources\\Onboarding' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Onboarding.php',
        'Mollie\\Api\\Resources\\Order' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Order.php',
        'Mollie\\Api\\Resources\\OrderCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/OrderCollection.php',
        'Mollie\\Api\\Resources\\OrderLine' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/OrderLine.php',
        'Mollie\\Api\\Resources\\OrderLineCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/OrderLineCollection.php',
        'Mollie\\Api\\Resources\\Organization' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Organization.php',
        'Mollie\\Api\\Resources\\OrganizationCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/OrganizationCollection.php',
        'Mollie\\Api\\Resources\\Payment' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Payment.php',
        'Mollie\\Api\\Resources\\PaymentCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/PaymentCollection.php',
        'Mollie\\Api\\Resources\\Permission' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Permission.php',
        'Mollie\\Api\\Resources\\PermissionCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/PermissionCollection.php',
        'Mollie\\Api\\Resources\\Profile' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Profile.php',
        'Mollie\\Api\\Resources\\ProfileCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/ProfileCollection.php',
        'Mollie\\Api\\Resources\\Refund' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Refund.php',
        'Mollie\\Api\\Resources\\RefundCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/RefundCollection.php',
        'Mollie\\Api\\Resources\\ResourceFactory' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/ResourceFactory.php',
        'Mollie\\Api\\Resources\\Settlement' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Settlement.php',
        'Mollie\\Api\\Resources\\SettlementCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/SettlementCollection.php',
        'Mollie\\Api\\Resources\\Shipment' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Shipment.php',
        'Mollie\\Api\\Resources\\ShipmentCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/ShipmentCollection.php',
        'Mollie\\Api\\Resources\\Subscription' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/Subscription.php',
        'Mollie\\Api\\Resources\\SubscriptionCollection' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Resources/SubscriptionCollection.php',
        'Mollie\\Api\\Types\\InvoiceStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/InvoiceStatus.php',
        'Mollie\\Api\\Types\\MandateMethod' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/MandateMethod.php',
        'Mollie\\Api\\Types\\MandateStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/MandateStatus.php',
        'Mollie\\Api\\Types\\OnboardingStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/OnboardingStatus.php',
        'Mollie\\Api\\Types\\OrderLineStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/OrderLineStatus.php',
        'Mollie\\Api\\Types\\OrderLineType' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/OrderLineType.php',
        'Mollie\\Api\\Types\\OrderStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/OrderStatus.php',
        'Mollie\\Api\\Types\\PaymentMethod' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/PaymentMethod.php',
        'Mollie\\Api\\Types\\PaymentStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/PaymentStatus.php',
        'Mollie\\Api\\Types\\ProfileStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/ProfileStatus.php',
        'Mollie\\Api\\Types\\RefundStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/RefundStatus.php',
        'Mollie\\Api\\Types\\SequenceType' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/SequenceType.php',
        'Mollie\\Api\\Types\\SettlementStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/SettlementStatus.php',
        'Mollie\\Api\\Types\\SubscriptionStatus' => __DIR__ . '/..' . '/mollie/mollie-api-php/src/Types/SubscriptionStatus.php',
        'Mollie_WC_Components_Styles' => __DIR__ . '/../..' . '/src/Mollie/WC/Components/Styles.php',
        'Mollie_WC_Components_StylesPropertiesDictionary' => __DIR__ . '/../..' . '/src/Mollie/WC/Components/StylesPropertiesDictionary.php',
        'Mollie_WC_Exception' => __DIR__ . '/../..' . '/src/Mollie/WC/Exception.php',
        'Mollie_WC_Exception_CouldNotConnectToMollie' => __DIR__ . '/../..' . '/src/Mollie/WC/Exception/CouldNotConnectToMollie.php',
        'Mollie_WC_Exception_IncompatiblePlatform' => __DIR__ . '/../..' . '/src/Mollie/WC/Exception/IncompatiblePlatform.php',
        'Mollie_WC_Exception_InvalidApiKey' => __DIR__ . '/../..' . '/src/Mollie/WC/Exception/InvalidApiKey.php',
        'Mollie_WC_Gateway_Abstract' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Abstract.php',
        'Mollie_WC_Gateway_AbstractSepaRecurring' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/AbstractSepaRecurring.php',
        'Mollie_WC_Gateway_AbstractSubscription' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/AbstractSubscription.php',
        'Mollie_WC_Gateway_Applepay' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Applepay.php',
        'Mollie_WC_Gateway_Bancontact' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Bancontact.php',
        'Mollie_WC_Gateway_BankTransfer' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/BankTransfer.php',
        'Mollie_WC_Gateway_Belfius' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Belfius.php',
        'Mollie_WC_Gateway_Creditcard' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Creditcard.php',
        'Mollie_WC_Gateway_DirectDebit' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/DirectDebit.php',
        'Mollie_WC_Gateway_EPS' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/EPS.php',
        'Mollie_WC_Gateway_Giftcard' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Giftcard.php',
        'Mollie_WC_Gateway_Giropay' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Giropay.php',
        'Mollie_WC_Gateway_Ideal' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Ideal.php',
        'Mollie_WC_Gateway_IngHomePay' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/IngHomePay.php',
        'Mollie_WC_Gateway_Kbc' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Kbc.php',
        'Mollie_WC_Gateway_KlarnaPayLater' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/KlarnaPayLater.php',
        'Mollie_WC_Gateway_KlarnaSliceIt' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/KlarnaSliceIt.php',
        'Mollie_WC_Gateway_MisterCash' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/MisterCash.php',
        'Mollie_WC_Gateway_MyBank' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/MyBank.php',
        'Mollie_WC_Gateway_PayPal' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/PayPal.php',
        'Mollie_WC_Gateway_Paysafecard' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Paysafecard.php',
        'Mollie_WC_Gateway_Przelewy24' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Przelewy24.php',
        'Mollie_WC_Gateway_Sofort' => __DIR__ . '/../..' . '/src/Mollie/WC/Gateway/Sofort.php',
        'Mollie_WC_Helper_Api' => __DIR__ . '/../..' . '/src/Mollie/WC/Helper/Api.php',
        'Mollie_WC_Helper_Data' => __DIR__ . '/../..' . '/src/Mollie/WC/Helper/Data.php',
        'Mollie_WC_Helper_OrderLines' => __DIR__ . '/../..' . '/src/Mollie/WC/Helper/OrderLines.php',
        'Mollie_WC_Helper_PaymentFactory' => __DIR__ . '/../..' . '/src/Mollie/WC/Helper/PaymentFactory.php',
        'Mollie_WC_Helper_PaymentMethodsIconUrl' => __DIR__ . '/../..' . '/src/Mollie/WC/Helper/PaymentMethodsIconUrl.php',
        'Mollie_WC_Helper_Settings' => __DIR__ . '/../..' . '/src/Mollie/WC/Helper/Settings.php',
        'Mollie_WC_Helper_Status' => __DIR__ . '/../..' . '/src/Mollie/WC/Helper/Status.php',
        'Mollie_WC_OrderLineStatus' => __DIR__ . '/../..' . '/src/Mollie/WC/OrderLineStatus.php',
        'Mollie_WC_Payment_Object' => __DIR__ . '/../..' . '/src/Mollie/WC/Payment/Object.php',
        'Mollie_WC_Payment_Order' => __DIR__ . '/../..' . '/src/Mollie/WC/Payment/Order.php',
        'Mollie_WC_Payment_OrderItemsRefunder' => __DIR__ . '/../..' . '/src/Mollie/WC/Payment/OrderItemsRefunder.php',
        'Mollie_WC_Payment_PartialRefundException' => __DIR__ . '/../..' . '/src/Mollie/WC/Payment/PartialRefundException.php',
        'Mollie_WC_Payment_Payment' => __DIR__ . '/../..' . '/src/Mollie/WC/Payment/Payment.php',
        'Mollie_WC_Payment_RefundLineItemsBuilder' => __DIR__ . '/../..' . '/src/Mollie/WC/Payment/RefundLineItemsBuilder.php',
        'Mollie_WC_Plugin' => __DIR__ . '/../..' . '/src/Mollie/WC/Plugin.php',
        'Mollie_WC_Settings_Components' => __DIR__ . '/../..' . '/src/Mollie/WC/Settings/Components.php',
        'Mollie_WC_Settings_Page_Components' => __DIR__ . '/../..' . '/src/Mollie/WC/Settings/Page/Components.php',
        'Psr\\Http\\Message\\MessageInterface' => __DIR__ . '/..' . '/psr/http-message/src/MessageInterface.php',
        'Psr\\Http\\Message\\RequestInterface' => __DIR__ . '/..' . '/psr/http-message/src/RequestInterface.php',
        'Psr\\Http\\Message\\ResponseInterface' => __DIR__ . '/..' . '/psr/http-message/src/ResponseInterface.php',
        'Psr\\Http\\Message\\ServerRequestInterface' => __DIR__ . '/..' . '/psr/http-message/src/ServerRequestInterface.php',
        'Psr\\Http\\Message\\StreamInterface' => __DIR__ . '/..' . '/psr/http-message/src/StreamInterface.php',
        'Psr\\Http\\Message\\UploadedFileInterface' => __DIR__ . '/..' . '/psr/http-message/src/UploadedFileInterface.php',
        'Psr\\Http\\Message\\UriInterface' => __DIR__ . '/..' . '/psr/http-message/src/UriInterface.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0e0f84c502efc5e060d5602a9841af8f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0e0f84c502efc5e060d5602a9841af8f::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit0e0f84c502efc5e060d5602a9841af8f::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit0e0f84c502efc5e060d5602a9841af8f::$classMap;

        }, null, ClassLoader::class);
    }
}
