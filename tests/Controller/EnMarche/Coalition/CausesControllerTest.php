<?php

namespace Tests\App\Controller\EnMarche;

use App\DataFixtures\ORM\LoadCauseData;
use App\Entity\Coalition\Cause;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\App\Controller\ControllerTestTrait;

/**
 * @group functional
 */
class CausesControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    private $causeRepository;

    public function testCausesPageIsForbiddenAsNotCoalitionModerator(): void
    {
        $this->authenticateAsAdherent($this->client, 'carl999@example.fr');

        $this->client->request(Request::METHOD_GET, '/espace-coalition/causes');
        $this->assertStatusCode(Response::HTTP_FORBIDDEN, $this->client);

        $this->authenticateAsAdherent($this->client, 'francis.brioul@yahoo.com');

        $this->client->request(Request::METHOD_GET, '/espace-coalition/causes');
        $this->assertStatusCode(Response::HTTP_FORBIDDEN, $this->client);
    }

    public function testSeeCausesPageAsCoalitionModerator(): void
    {
        $this->authenticateAsAdherent($this->client, 'jacques.picard@en-marche.fr');

        $crawler = $this->client->request(Request::METHOD_GET, '/espace-coalition/causes');
        $this->assertStatusCode(Response::HTTP_OK, $this->client);

        $this->assertCount(1, $causes = $crawler->filter('.datagrid table tbody tr'));
        $causeFields = $causes->eq(0)->filter('td');
        $this->assertSame('Cause en attente', $causeFields->eq(1)->text());
        $this->assertStringContainsString('Jacques (Paris 8e)', $causeFields->eq(2)->text());
        $this->assertStringContainsString('jacques.picard@en-marche.fr', $causeFields->eq(2)->text());
        $this->assertSame('Justice', $causeFields->eq(4)->text());
        $this->assertSame('Lorem ipsum dolor sit amet, consectetur adipiscing elit.', $causeFields->eq(5)->text(null, true));
        $this->assertSame('En attente', $causeFields->eq(7)->text());
    }

    public function testChangeCauseStatusAsCoalitionModerator(): void
    {
        /** @var Cause $cause */
        $cause = $this->causeRepository->findOneBy(['uuid' => LoadCauseData::CAUSE_7_UUID]);

        $this->assertSame(Cause::STATUS_PENDING, $cause->getStatus());

        $this->authenticateAsAdherent($this->client, 'jacques.picard@en-marche.fr');

        // approve
        $this->client->request(Request::METHOD_POST, '/espace-coalition/causes/approuver',
            ['ids' => [$cause->getId()]],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $this->assertStatusCode(Response::HTTP_OK, $this->client);

        $this->manager->clear();

        $this->assertSame(Cause::STATUS_APPROVED, $cause->getStatus());
        $this->assertCountMails(1, 'CauseApprovalMessage', 'jacques.picard@en-marche.fr');

        // refuse
        $this->client->request(Request::METHOD_POST, '/espace-coalition/causes/refuser',
            ['ids' => [$cause->getId()]],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $this->assertStatusCode(Response::HTTP_OK, $this->client);

        $this->manager->clear();
        $cause = $this->causeRepository->findOneBy(['uuid' => LoadCauseData::CAUSE_7_UUID]);

        $this->assertSame(Cause::STATUS_REFUSED, $cause->getStatus());

        // approve
        $this->client->request(Request::METHOD_POST, '/espace-coalition/causes/approuver',
            ['ids' => [$cause->getId()]],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $this->assertStatusCode(Response::HTTP_OK, $this->client);

        $this->manager->clear();
        $cause = $this->causeRepository->findOneBy(['uuid' => LoadCauseData::CAUSE_7_UUID]);

        $this->assertSame(Cause::STATUS_APPROVED, $cause->getStatus());
        $this->assertCountMails(2, 'CauseApprovalMessage', 'jacques.picard@en-marche.fr');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->init();

        $this->causeRepository = $this->getCauseRepository();
    }

    protected function tearDown(): void
    {
        $this->kill();

        $this->causeRepository = null;

        parent::tearDown();
    }
}
