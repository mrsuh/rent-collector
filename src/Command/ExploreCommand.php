<?php

namespace App\Command;

use App\Document\City\CityModel;
use App\Document\Parse\Record\RecordModel;
use App\Explorer\Tomita\TomitaExplorer;
use App\Filter\BlackListFilter;
use App\Parser\ParserFactory;
use App\Request\VkPrivateRequest;
use App\Request\VkPublicRequest;
use Psr\Log\LoggerInterface;
use Schema\City\City;
use Schema\Note\Note;
use Schema\Parse\Record\Record;
use Schema\Parse\Record\Source;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExploreCommand extends Command
{
    protected static $defaultName = 'app:explore';

    private $logger;
    private $vkPrivateRequest;
    private $vkPublicRequest;
    private $tomitaExplorer;
    private $recordModel;
    private $blackListFilter;
    private $parserFactory;
    private $cityModel;

    public function __construct(
        LoggerInterface $logger,
        VkPrivateRequest $vkPrivateRequest,
        VkPublicRequest $vkPublicRequest,
        TomitaExplorer $tomitaExplorer,
        RecordModel $recordModel,
        BlackListFilter $blackListFilter,
        ParserFactory $parserFactory,
        CityModel $cityModel
    ) {
        $this->logger           = $logger;
        $this->vkPrivateRequest = $vkPrivateRequest;
        $this->vkPublicRequest  = $vkPublicRequest;
        $this->tomitaExplorer   = $tomitaExplorer;
        $this->recordModel      = $recordModel;
        $this->blackListFilter  = $blackListFilter;
        $this->parserFactory    = $parserFactory;
        $this->cityModel        = $cityModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption(
                'city',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'sankt-peterburg',
                ['sankt-peterburg']
            )->addOption(
                'valid-period',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                '2 days',
                ['2 days']
            )->addOption(
                'search-query',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'снять квартиру спб',
                ['снять квартиру спб']
            )->addOption(
                'max-valid-results',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                10,
                [10]
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cities          = $input->getOption('city');
        $queries         = $input->getOption('search-query');
        $validPeriods    = $input->getOption('valid-period');
        $maxValidResults = $input->getOption('max-valid-results');

        if (count($cities) !== count($queries)) {
            $output->writeln('<error>Count cities !== count queries</error>');

            exit(1);
        }

        if (count($cities) !== count($validPeriods)) {
            $output->writeln('<error>Count cities !== count validPeriods</error>');

            exit(1);
        }

        if (count($cities) !== count($maxValidResults)) {
            $output->writeln('<error>Count cities !== count maxValidResults</error>');

            exit(1);
        }

        foreach ($cities as $index => $cityName) {
            $city = $this->cityModel->findOneByShortName($cityName);
            if ($city === null) {
                $output->writeln(
                    sprintf('<error>There is no city with short name %s</error>', $input->getOption('city'))
                );

                continue;
            }

            $query          = $queries[$index];
            $validPeriod    = $validPeriods[$index];
            $maxValidResult = (int)$maxValidResults[$index];

            $validPeriodDateTime = (new \DateTime())->modify(sprintf('- %s', $validPeriod));

            $this->explore($city, $query, $maxValidResult, $validPeriodDateTime);
        }
    }

    private function explore(City $city, string $query, int $maxValidResults, \DateTime $validPeriodDateTime): void
    {
        usleep(200000);

        $this->logger->info(
            'Exploring city',
            [
                'city'                => $city->getShortName(),
                'query'               => $query,
                'maxValidResults'     => $maxValidResults,
                'validPeriodDateTime' => $validPeriodDateTime->format('Y-m-d H:i:s'),
            ]
        );

        $newRecords = [];
        $count      = 0;
        foreach ($this->getGroups($query, 200) as $group) {

            if ($count >= $maxValidResults) {

                $this->logger->debug(
                    'Explore group stopped. Limit by count',
                    [
                        'city'  => $city->getShortName(),
                        'count' => $count,
                    ]
                );

                break;
            }

            $group_id = $group['id'];

            $this->logger->debug(
                'Explore group...',
                [
                    'city'    => $city->getShortName(),
                    'groupId' => $group_id,
                    'count'   => $count,
                ]
            );

            $record = $this->exploreGroup($city, $group, $validPeriodDateTime);

            if ($record === null) {
                $this->logger->debug(
                    'Explore group is invalid',
                    [
                        'city'    => $city->getShortName(),
                        'groupId' => $group_id,
                    ]
                );

                continue;
            }

            $newRecords[] = $record;
            $count++;
        }

        $this->deleteInvalidRecords($city);

        $this->logger->info(
            'Exploring city done',
            [
                'city'                => $city->getShortName(),
                'query'               => $query,
                'maxValidResults'     => $maxValidResults,
                'validPeriodDateTime' => $validPeriodDateTime->format('Y-m-d H:i:s'),
                'records'             => $count,
            ]
        );
    }

    private function getGroups(string $query, int $maxResults): array
    {
        usleep(200000);

        try {

            $response = $this->vkPrivateRequest->groupsSearch($query, $maxResults);

        } catch (\Exception $e) {

            $this->logger->error(
                'group search request failed',
                [
                    'exception' => $e->getMessage(),
                ]
            );

            return [];
        }

        $content = (string)$response->getBody();
        $data    = json_decode($content, true);

        if (isset($data['response']['error'])) {
            $this->logger->debug('Response has errors', ['response' => $content]);

            return [];
        }

        if (!isset($data['response']['items'])) {
            $this->logger->error('Invalid response', ['response' => $content]);

            return [];
        }

        return $data['response']['items'];
    }

    private function exploreGroup(City $city, array $group, \DateTime $validPeriodDateTime): ?Record
    {
        $group_id = $group['id'];

        $exists_record = $this->recordModel->findOneByName($group_id);
        if ($exists_record !== null) {
            $this->recordModel->delete($exists_record);
        }

        if ($group['is_closed']) {

            $this->logger->debug(
                'Group is closed',
                [
                    'city'     => $city->getShortName(),
                    'group_id' => $group_id,
                ]
            );

            return null;
        }

        $is_group = 'group' === $group['type'];

        $record = (new Record())
            ->setCity($city->getShortName())
            ->setLink(sprintf('https://vk.com/%s%s', ($is_group ? 'club' : 'public'), $group_id))
            ->setName($group_id);

        $sources = [];
        if ($this->isValidWall($group_id, $city, $validPeriodDateTime)) {

            $this->logger->debug(
                'Add wall source from api',
                [
                    'city'     => $city->getShortName(),
                    'group_id' => $group_id,
                ]
            );

            $sources[] =
                (new Source())
                    ->setId('wall')
                    ->setCity($city->getShortName())
                    ->setType(Source::TYPE_VK_WALL)
                    ->setLink(sprintf('https://vk.com/%s%s', ($is_group ? 'club' : 'public'), $group_id))
                    ->setParameters(
                        json_encode(
                            [
                                'owner_id' => sprintf('-%s', $group_id),
                                'count'    => 50,
                            ]
                        )
                    );
        }

        foreach ($this->getTopics($group_id) as $topic) {

            $topic_id = $topic['id'];

            if ($this->isValidTopic($group_id, $topic_id, $city, $validPeriodDateTime)) {

                $this->logger->debug(
                    'Add comment source from api',
                    [
                        'city'     => $city->getShortName(),
                        'group_id' => $group_id,
                        'topic_id' => $topic_id,
                    ]
                );

                $sources[] =
                    (new Source())
                        ->setId($topic_id)
                        ->setCity($city->getShortName())
                        ->setType(Source::TYPE_VK_COMMENT)
                        ->setLink(sprintf('https://vk.com/topic-%s_%s', $group_id, $topic_id))
                        ->setParameters(
                            json_encode(
                                [
                                    'group_id' => $group_id,
                                    'topic_id' => $topic_id,
                                    'count'    => 100,
                                ]
                            )
                        );
            }
        }

        if (empty($sources)) {

            $this->logger->debug(
                'Empty sources',
                [
                    'city'     => $city->getShortName(),
                    'group_id' => $group_id,
                ]
            );

            return null;
        }

        $record->setSources($sources);
        $this->recordModel->create($record);

        return $record;
    }

    private function isValidWall(int $group_id, City $city, \DateTime $validPeriodDateTime): bool
    {
        usleep(200000);

        try {

            $response = $this->vkPublicRequest->getWallRecords($group_id, 10, 0);

        } catch (\Exception $e) {

            $this->logger->error(
                'get wall records request failed',
                [
                    'exception' => $e->getMessage(),
                ]
            );

            return false;
        }

        $content = (string)$response->getBody();
        $data    = json_decode($content, true);

        if (isset($data['response']['error'])) {
            $this->logger->debug('Response has errors', ['response' => $content]);

            return false;
        }

        if (!isset($data['response']['items'])) {
            $this->logger->error('Invalid response', ['response' => $content]);

            return false;
        }

        $count       = 0;
        $contact_ids = [];
        foreach ($data['response']['items'] as $item) {
            $contact_id = $this->parserFactory->init(
                (new Source())->setType(Source::TYPE_VK_WALL)->setCity($city->getShortName()),
                $item
            )->contactId();

            if (empty($contact_id)) {

                $this->logger->debug(
                    'Explore wall note fail',
                    [
                        'reason' => 'empty contact_id',
                    ]
                );

                continue;
            }

            if (!$this->blackListFilter->isAllow($contact_id)) {
                $this->logger->debug(
                    'Explore wall note fail',
                    [
                        'contact_id' => $contact_id,
                        'reason'     => 'contact_id filter are not allow',
                    ]
                );

                continue;
            }

            if ((int)$contact_id === (int)$group_id) {

                $this->logger->debug(
                    'Explore wall note fail',
                    [
                        'reason' => 'contact_id is same as group_id',
                    ]
                );

                continue;
            }

            if (in_array($contact_id, $contact_ids)) {

                $this->logger->debug(
                    'Explore wall note fail',
                    [
                        'reason' => 'contact_id is already in used',
                    ]
                );

                continue;
            }

            $itemTimestamp = $item['date'];
            if ($itemTimestamp < $validPeriodDateTime->getTimestamp()) {
                $this->logger->debug(
                    'Explore wall note fail',
                    [
                        'reason' => '$itemTimestamp < $validPeriodDateTime',
                        'data'   => $item,
                    ]
                );
                continue;
            }

            $contact_ids[] = $contact_id;

            $text = $item['text'];

            if (!$this->blackListFilter->isAllow($text)) {

                $this->logger->debug(
                    'Explore wall note fail',
                    [
                        'description' => $text,
                        'reason'      => 'description filter are not allow',
                    ]
                );

                continue;
            }

            $tomita = $this->tomitaExplorer->explore($text);
            if ((int)$tomita->getType() === Note::TYPE_ERR) {

                $this->logger->debug(
                    'Explore wall note fail',
                    [
                        'description' => $text,
                        'reason'      => 'tomita invalid type',
                    ]
                );

                continue;
            }

            $this->logger->debug('Explore wall note success');

            $count++;

            if ($count > 3) {
                break;
            }
        }

        return $count > 3;
    }

    private function getTopics(int $group_id): array
    {
        usleep(200000);

        try {

            $response = $this->vkPrivateRequest->boardGetTopics($group_id);

        } catch (\Exception $e) {

            $this->logger->error(
                'get board topics request failed',
                [
                    'exception' => $e->getMessage(),
                ]
            );

            return [];
        }

        $content = (string)$response->getBody();
        $data    = json_decode($content, true);

        if (isset($data['response']['error'])) {
            $this->logger->debug('Response has errors', ['response' => $content]);

            return [];
        }

        if (!isset($data['response']['items'])) {
            $this->logger->error('Invalid response', ['response' => $content]);

            return [];
        }

        return $data['response']['items'];
    }

    private function isValidTopic(int $group_id, int $topic_id, City $city, \DateTime $validPeriodDateTime): bool
    {
        usleep(200000);

        try {

            $response = $this->vkPublicRequest->getCommentRecords($group_id, $topic_id, 10, 0);

        } catch (\Exception $e) {

            $this->logger->error(
                'get comment records request failed',
                [
                    'exception' => $e->getMessage(),
                ]
            );

            return false;
        }

        $content = (string)$response->getBody();
        $data    = json_decode($content, true);

        if (isset($data['response']['error'])) {
            $this->logger->debug('Response has errors', ['response' => $content]);

            return false;
        }

        if (!isset($data['response']['items'])) {
            $this->logger->error('Invalid response', ['response' => $content]);

            return false;
        }

        $count       = 0;
        $contact_ids = [];
        foreach ($data['response']['items'] as $item) {
            $contact_id = $this->parserFactory->init(
                (new Source())
                    ->setType(Source::TYPE_VK_COMMENT)
                    ->setCity($city->getShortName()),
                $item
            )->contactId();

            if (empty($contact_id)) {

                $this->logger->debug(
                    'Explore topic note fail',
                    [
                        'reason' => 'empty contact_id',
                    ]
                );

                continue;
            }

            if (in_array($contact_id, $contact_ids)) {

                $this->logger->debug(
                    'Explore topic note fail',
                    [
                        'reason' => 'contact_id is already in used',
                    ]
                );

                continue;
            }

            $itemTimestamp = $item['date'];

            if ($itemTimestamp < $validPeriodDateTime->getTimestamp()) {
                $this->logger->debug(
                    'Explore topic note fail',
                    [
                        'reason' => '$itemTimestamp < $validPeriodDateTime',
                    ]
                );
                continue;
            }

            $contact_ids[] = $contact_id;

            $text = $item['text'];

            if (!$this->blackListFilter->isAllow($text)) {

                $this->logger->debug(
                    'Explore topic note fail',
                    [
                        'description' => $text,
                        'reason'      => 'description filter are not allow',
                    ]
                );

                continue;
            }

            $tomita = $this->tomitaExplorer->explore($text);

            if ((int)$tomita->getType() === Note::TYPE_ERR) {
                $this->logger->debug(
                    'Explore topic note fail',
                    [
                        'description' => $text,
                        'reason'      => 'tomita invalid type',
                    ]
                );

                continue;
            }

            $this->logger->debug('Explore topic note success');

            $count++;

            if ($count > 3) {
                break;
            }
        }

        return $count > 3;
    }

    private function deleteInvalidRecords(City $city): void
    {
        $records_all = $this->recordModel->findByCity($city->getShortName());

        $chunks = array_chunk($records_all, 400);

        foreach ($chunks as $records) {

            $records_by_id = [];
            /** @var Record $record */
            foreach ($records as $record) {
                $records_by_id[$record->getName()] = $record;
            }

            try {

                $response = $this->vkPrivateRequest->groupsGetById(array_keys($records_by_id));

            } catch (\Exception $e) {

                $this->logger->error(
                    'groups get by id request failed',
                    [
                        'exception' => $e->getMessage(),
                    ]
                );

                return;
            }

            $data = json_decode((string)$response->getBody(), true);

            if (!isset($data['response'])) {
                $this->logger->error('Response has not key "response"', ['response' => (string)$response->getBody()]);

                return;
            }

            foreach ($data['response'] as $item) {
                $id = $item['id'];
                if ($item['is_closed'] && array_key_exists($id, $records_by_id)) {
                    $record = $records_by_id[$id];

                    $this->logger->debug(
                        'Group is closed. Delete from DB',
                        [
                            'group_id' => $id,
                        ]
                    );

                    $this->recordModel->delete($record);
                }
            }
        }
    }
}
