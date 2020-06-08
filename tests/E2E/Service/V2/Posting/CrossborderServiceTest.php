<?php declare(strict_types=1);

namespace Gam6itko\OzonSeller\Tests\E2E\Service\V2\Posting;

use Gam6itko\OzonSeller\Enum\SortDirection;
use Gam6itko\OzonSeller\Enum\Status;
use Gam6itko\OzonSeller\Exception\BadRequestException;
use Gam6itko\OzonSeller\Exception\NotFoundException;
use Gam6itko\OzonSeller\Service\V1\CategoriesService;
use Gam6itko\OzonSeller\Service\V2\Posting\CrossborderService;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * @coversDefaultClass \Gam6itko\OzonSeller\Service\V2\Posting\CrossborderService
 * @group  v2
 * @group  e2e
 *
 * @author Alexander Strizhak <gam6itko@gmail.com>
 */
class CrossborderServiceTest extends TestCase
{
    /** @var CategoriesService */
    private static $svc;

    public static function setUpBeforeClass(): void
    {
        self::$svc = new CrossborderService((int) $_SERVER['CLIENT_ID'], $_SERVER['API_KEY'], $_SERVER['API_URL']);
    }

    protected function setUp(): void
    {
        sleep(1); //fix 429 Too Many Requests
    }

    /**
     * @covers ::list
     */
    public function testList()
    {
        self::$svc->list(SortDirection::ASC, 0, 10, [
            'since'  => new \DateTime('2019-01-01'),
            'to'     => new \DateTime('2020-01-01'),
            'status' => Status::AWAITING_APPROVE,
        ]);
        self::assertTrue(true);
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $this->expectException(NotFoundException::class);
        self::$svc->get('123456790');
    }

    /**
     * @covers ::unfulfilledList
     */
    public function testUnfulfilledList()
    {
        self::$svc->unfulfilledList(Status::AWAITING_APPROVE);
        self::assertTrue(true);
    }

    /**
     * @covers ::approve
     */
    public function testApprove()
    {
        $this->expectException(NotFoundException::class);
        self::$svc->approve('123456');
        self::assertTrue(true);
    }

    /**
     * @covers ::cancel
     */
    public function testCancel()
    {
        $this->expectException(BadRequestException::class);
        self::$svc->cancel('39268230-0002-3', '149123456', 349, 'Cancel reason');
        self::assertTrue(true);
    }

    /**
     * @covers ::cancelReasons
     */
    public function testCancelReasons()
    {
        $result = self::$svc->cancelReasons();
        self::assertNotNull($result);
        self::assertArrayHasKey('id', $result[0]);
        self::assertArrayHasKey('title', $result[0]);
        self::assertArrayHasKey('type_id', $result[0]);
    }

    /**
     * @covers ::ship
     */
    public function testShip()
    {
        $this->expectException(NotFoundException::class);
        self::$svc->ship('39268230-0002-3', 'AB123456CD', 15109877837000, [
            [
                'quantity' => 2,
                'sku'      => 100056,
            ],
        ]);
        self::assertTrue(true);
    }

    /**
     * @covers ::setLogger
     */
    public function testLoggerDebug()
    {
        $logger = new TestLogger();
        self::$svc->setLogger($logger);

        self::$svc->list(SortDirection::ASC, 0, 10, [
            'since'  => new \DateTime('2019-01-01'),
            'to'     => new \DateTime('2020-01-01'),
            'status' => Status::AWAITING_APPROVE,
        ]);

        self::assertCount(2, $logger->records);
        $req = $logger->records[0];
        self::assertEquals('request POST /v2/posting/crossborder/list', $req['message']);
        self::assertEquals('{"body":"{\"filter\":{\"since\":\"2019-01-01T00:00:00+00:00\",\"to\":\"2020-01-01T00:00:00+00:00\",\"status\":\"awaiting_approve\"},\"dir\":\"asc\",\"offset\":0,\"limit\":10}"}', \GuzzleHttp\json_encode($req['context']));
        unset($req);

        $resp = $logger->records[1];
        self::assertEquals('response POST /v2/posting/crossborder/list', $resp['message']);
        self::assertEquals('{"result":[]}', \GuzzleHttp\json_encode($resp['context']));
    }

    /**
     * @covers ::setLogger
     */
    public function testLoggerError()
    {
        $logger = new TestLogger();
        self::$svc->setLogger($logger);

        try {
            self::$svc->ship('39268230-0002-3', 'AB123456CD', 15109877837000, [
                [
                    'quantity' => 2,
                    'sku'      => 100056,
                ],
            ]);
            self::assertTrue(true);
        } catch (NotFoundException $exc) {
            self::assertCount(2, $logger->records);
            $req = $logger->records[0];
            self::assertEquals('request POST /v2/posting/crossborder/ship', $req['message']);
            self::assertEquals('{"body":"{\"posting_number\":\"39268230-0002-3\",\"tracking_number\":\"AB123456CD\",\"shipping_provider_id\":15109877837000,\"items\":[{\"quantity\":2,\"sku\":100056}]}"}', \GuzzleHttp\json_encode($req['context']));
            unset($req);

            $err = $logger->records[1];
            self::assertEquals('error', $err['level']);
            self::assertEquals('Client error: `POST http://cb-api.ozonru.me/v2/posting/crossborder/ship` resulted in a `404 Not Found` response:
{"error":{"code":"NOT_FOUND_ERROR","message":"No query results for model [App\\\\Models\\\\OrderSeller].","data":[]}}

', $err['message']);
        }
    }
}
