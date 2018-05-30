<?php

namespace AppBundle\Command;

use AppBundle\Model\Document\Parse\Record\RecordModel;
use AppBundle\Model\Document\Parse\Record\SourceModel;
use AppBundle\Model\Logic\Explorer\Tomita\TomitaExplorer;
use AppBundle\Model\Logic\Filter\BlackList\DescriptionFilter;
use AppBundle\Request\VkPrivateRequest;
use AppBundle\Request\VkPublicRequest;
use Monolog\Logger;
use Schema\Note\Note;
use Schema\Parse\Record\Record;
use Schema\Parse\Record\Source;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Model\Logic\Parser\ParserFactory;

class ExploreCommand extends ContainerAwareCommand
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var VkPublicRequest
     */
    private $request_public;

    /**
     * @var VkPrivateRequest
     */
    private $request_private;

    /**
     * @var TomitaExplorer
     */
    private $explorer;

    /**
     * @var RecordModel
     */
    private $model_parse_record;

    /**
     * @var SourceModel
     */
    private $model_parse_source;

    /**
     * @var DescriptionFilter
     */
    private $filter_description;

    /**
     * @var ParserFactory
     */
    private $parser;

    protected function configure()
    {
        $this->setName('app:explore-groups')->addOption(
            'city',
            null,
            InputOption::VALUE_OPTIONAL,
            null
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = $this->getContainer()->get('logger');

        $this->request_private = $this->getContainer()->get('request.private.vk');

        $this->request_public = $this->getContainer()->get('request.public.vk');

        $this->explorer                  = $this->getContainer()->get('explorer.tomita');
        $this->model_parse_record        = $this->getContainer()->get('model.document.parse.record');
        $this->model_parse_source        = new SourceModel();
        $this->filter_description        = $this->getContainer()->get('filter.black_list.description');
        $this->parser = $this->getContainer()->get('parser.factory');

        $query       = 'снять квартиру';
        $model_city  = $this->getContainer()->get('model.document.city');
        $city_option = $input->getOption('city');
        foreach ($model_city->findAll() as $city) {

            $city_id   = $city->getVkId();
            $city_name = $city->getShortName();

            if (!empty($city_option) && $city_name !== $city_option) {

                continue;
            }

            $this->logger->info('Explore city', [
                'city'       => $city_name,
                'city_vk_id' => $city_id,
                'query'      => $query
            ]);

            usleep(200000);
            $groups = $this->getGroups($city_id, $query);

            $count = 0;
            foreach ($groups as $group) {

                if ($count >= 100) {

                    $this->logger->debug('Explore city stop. Limit by count', [
                        'city'  => $city_name,
                        'query' => $query
                    ]);

                    break;
                }

                $group_id = $group['id'];

                $this->logger->info('Explore group...', [
                    'city'     => $city_name,
                    'group_id' => $group_id,
                    'count'    => $count
                ]);

                if ($group['is_closed']) {

                    $this->logger->info('Group is closed', [
                        'city'     => $city_name,
                        'group_id' => $group_id
                    ]);

                    $record = $this->model_parse_record->findOneByName($group_id);
                    if (null !== $record) {

                        $this->logger->info('Group is closed. Delete from DB', [
                            'city'     => $city_name,
                            'group_id' => $group_id
                        ]);

                        $this->model_parse_record->delete($record);
                    }

                    continue;
                }

                $group_type = 'group' === $group['type'];

                if ($this->exploreGroup($city_name, $group_id, $group_type)) {
                    $this->logger->info('Explore group is valid', [
                        'city'     => $city_name,
                        'group_id' => $group_id
                    ]);

                    $count++;
                } else {
                    $this->logger->info('Explore group is invalid', [
                        'city'     => $city_name,
                        'group_id' => $group_id
                    ]);
                }
            }
        }

        $this->deleteInvalidRecords();
    }

    /**
     * @return array|bool
     */
    private function deleteInvalidRecords()
    {
        $records_all = $this->model_parse_record->findAll();


        $chunks = array_chunk($records_all, 400);

        foreach ($chunks as $records) {

            $records_by_id = [];
            foreach ($records as $record) {
                $records_by_id[$record->getName()] = $record;
            }

            try {

                $response = $this->request_private->groupsGetById(array_keys($records_by_id));

            } catch (\Exception $e) {

                $this->logger->error('groups get by id request failed', [
                    'exception' => $e->getMessage()
                ]);

                return false;
            }

            $content = $response->getBody()->getContents();
            $data    = json_decode($content, true);

            if (!is_array($data)) {
                $this->logger->error('Response has invalid json', ['response' => $content]);

                return [];
            }

            if (!array_key_exists('response', $data)) {
                $this->logger->error('Response has not key "response"', ['response' => $content]);

                return [];
            }

            foreach ($data['response'] as $item) {
                $id = $item['id'];
                if ($item['is_closed'] && array_key_exists($id, $records_by_id)) {
                    $record = $records_by_id[$id];

                    $this->logger->info('Group is closed. Delete from DB', [
                        'group_id' => $id
                    ]);

                    $this->model_parse_record->delete($record);
                }
            }
        }

        return true;
    }

    /**
     * @param string $city
     * @param int    $group_id
     * @param bool   $is_group
     * @return bool
     */
    private function exploreGroup(string $city, int $group_id, bool $is_group)
    {
        $exists_record = $this->model_parse_record->findOneByName($group_id);

        $record = new Record();
        $record->setCity($city);
        $record->setLink(sprintf('https://vk.com/%s%s', ($is_group ? 'club' : 'public'), $group_id));
        $record->setName($group_id);

        $sources = [];
        if (null === $exists_record && $this->isValidWall($group_id, $city)) {

            $this->logger->debug('Add wall source from api', [
                'city'     => $city,
                'group_id' => $group_id
            ]);

            $sources[] =
                (new Source())
                    ->setId('wall')
                    ->setCity($city)
                    ->setType(Source::TYPE_VK_WALL)
                    ->setLink(sprintf('https://vk.com/%s%s', ($is_group ? 'club' : 'public'), $group_id))
                    ->setParameters(sprintf('{"owner_id": "-%s", "count": 50}', $group_id));
        } elseif (null !== $exists_record && null !== $exists_source = $this->model_parse_source->findOneById($exists_record, 'wall')) {

            $this->logger->debug('Add wall source from DB', [
                'city'     => $city,
                'group_id' => $group_id
            ]);
            $sources[] = $exists_source;
        }

        $topics = $this->getTopics($group_id);

        foreach ($topics as $topic) {

            $topic_id = $topic['id'];

            if (null !== $exists_record && null !== $exists_source = $this->model_parse_source->findOneById($exists_record, $topic_id)) {

                $this->logger->debug('Add comment source from DB', [
                    'city'     => $city,
                    'group_id' => $group_id
                ]);

                $sources[] = $exists_source;

                continue;
            }

            if ($this->isValidTopic($group_id, $topic_id, $city)) {

                $this->logger->debug('Add comment source from api', [
                    'city'     => $city,
                    'group_id' => $group_id
                ]);

                $sources[] =
                    (new Source())
                        ->setId($topic_id)
                        ->setCity($city)
                        ->setType(Source::TYPE_VK_COMMENT)
                        ->setLink(sprintf('https://vk.com/topic-%s_%s', $group_id, $topic_id))
                        ->setParameters(sprintf('{"group_id": "%s", "topic_id": "%s", "count": "100"}', $group_id, $topic_id));
            }
        }

        if (empty($sources)) {

            $this->logger->debug('Empty sources', [
                'city'     => $city,
                'group_id' => $group_id
            ]);

            return false;
        }

        if (null !== $exists_record) {
            $exists_record->setSources($sources);

            return true;
        }

        $record->setSources($sources);
        $this->model_parse_record->create($record);

        return true;
    }

    /**
     * @param int    $city_id
     * @param string $query
     * @return array|bool
     */
    private function getGroups(int $city_id, string $query)
    {
        usleep(200000);

        try {

            $response = $this->request_private->groupsSearch($city_id, $query);

        } catch (\Exception $e) {

            $this->logger->error('group search request failed', [
                'exception' => $e->getMessage()
            ]);

            return false;
        }

        $content = $response->getBody()->getContents();
        $data    = json_decode($content, true);

        if (!is_array($data)) {
            $this->logger->error('Response has invalid json', ['response' => $content]);

            return [];
        }

        if (!array_key_exists('response', $data)) {
            $this->logger->error('Response has not key "response"', ['response' => $content]);

            return [];
        }

        if (!array_key_exists('items', $data['response'])) {
            $this->logger->error('Response has not key "items"', ['response' => $content]);

            return [];
        }

        return $data['response']['items'];
    }

    /**
     * @param int $group_id
     * @return array|bool
     */
    private function getTopics(int $group_id)
    {
        usleep(200000);

        try {

            $response = $this->request_private->boardGetTopics($group_id);

        } catch (\Exception $e) {

            $this->logger->error('get board topics request failed', [
                'exception' => $e->getMessage()
            ]);

            return false;
        }

        $content = $response->getBody()->getContents();
        $data    = json_decode($content, true);

        if (!is_array($data)) {
            $this->logger->error('Response has invalid json', ['response' => $content]);

            return [];
        }

        if (!array_key_exists('response', $data)) {
            $this->logger->error('Response has not key "response"', ['response' => $content]);

            return [];
        }

        if (!array_key_exists('items', $data['response'])) {
            $this->logger->error('Response has not key "items"', ['response' => $content]);

            return [];
        }

        return $data['response']['items'];
    }

    /**
     * @param int    $group_id
     * @param int    $topic_id
     * @param string $city_name
     * @return bool
     */
    private function isValidTopic(int $group_id, int $topic_id, string $city_name): bool
    {
        usleep(200000);

        try {

            $response = $this->request_public->getCommentRecords([
                'group_id'         => $group_id,
                'topic_id'         => $topic_id,
                'count'            => 10,
                'start_comment_id' => 10
            ]);

        } catch (\Exception $e) {

            $this->logger->error('get comment records request failed', [
                'exception' => $e->getMessage()
            ]);

            return false;
        }

        $content = $response->getBody()->getContents();
        $data    = json_decode($content, true);

        if (!is_array($data)) {
            $this->logger->error('Response has invalid json', ['response' => $content]);

            return false;
        }

        if (!array_key_exists('response', $data)) {
            $this->logger->error('Response has not key "response"', ['response' => $content]);

            return false;
        }

        if (!array_key_exists('items', $data['response'])) {
            $this->logger->error('Response has not key "items"', ['response' => $content]);

            return false;
        }

        $items = $data['response']['items'];

        $count       = 0;
        $contact_ids = [];
        foreach ($items as $item) {
            $contact_id = $this->parser->init((new Source())->setType(Source::TYPE_VK_COMMENT)->setCity($city_name), $item)->contactId();

            if (empty($contact_id)) {

                $this->logger->debug('Explore topic note fail', [
                    'note'   => $item['text'],
                    'reason' => 'empty contact_id'
                ]);

                continue;
            }

            if (in_array($contact_id, $contact_ids)) {

                $this->logger->debug('Explore topic note fail', [
                    'note'   => $item['text'],
                    'reason' => 'contact_id is already in used'
                ]);

                continue;
            }

            $contact_ids[] = $contact_id;

            $text = $item['text'];

            $is_allow = $this->filter_description->isAllow($text);

            if (!$is_allow) {

                $this->logger->debug('Explore topic note fail', [
                    'note'   => $item['text'],
                    'reason' => 'description filter are not allow'
                ]);

                continue;
            }

            $tomita = $this->explorer->explore($text);

            $type = $tomita->getType();

            if ((int)$type === Note::TYPE_ERR) {

                $this->logger->debug('Explore topic note fail', [
                    'note'   => $item['text'],
                    'reason' => 'tomita invalid type'
                ]);

                continue;
            }

            $this->logger->debug('Explore topic note success', [
                'note' => $item['text']
            ]);

            $count++;

        }

        return $count > 3;
    }

    /**
     * @param int    $group_id
     * @param string $city_name
     * @return bool
     */
    private function isValidWall(int $group_id, string $city_name): bool
    {
        usleep(200000);

        try {

            $response = $this->request_public->getWallRecords([
                'owner_id' => $group_id,
                'count'    => 10,
                'offset'   => 10
            ]);

        } catch (\Exception $e) {

            $this->logger->error('get wall records request failed', [
                'exception' => $e->getMessage()
            ]);

            return false;
        }

        $content = $response->getBody()->getContents();
        $data    = json_decode($content, true);

        if (!is_array($data)) {
            $this->logger->error('Response has invalid json', ['response' => $content]);

            return false;
        }

        if (!array_key_exists('response', $data)) {
            $this->logger->error('Response has not key "response"', ['response' => $content]);

            return false;
        }

        if (!array_key_exists('items', $data['response'])) {
            $this->logger->error('Response has not key "items"', ['response' => $content]);

            return false;
        }

        $items = $data['response']['items'];

        $count       = 0;
        $contact_ids = [];
        foreach ($items as $item) {
            $contact_id = $this->parser->init((new Source())->setType(Source::TYPE_VK_WALL)->setCity($city_name), $item)->contactId();

            $this->logger->info('CONTACT ID', ['ID' => $contact_id]);

            if (empty($contact_id)) {

                $this->logger->debug('Explore wall note fail', [
                    'note'   => $item['text'],
                    'reason' => 'empty contact_id'
                ]);

                continue;
            }

            if ((int)$contact_id === (int)$group_id) {

                $this->logger->debug('Explore wall note fail', [
                    'note'   => $item['text'],
                    'reason' => 'contact_id is same as group_id'
                ]);

                continue;
            }

            if (in_array($contact_id, $contact_ids)) {

                $this->logger->debug('Explore wall note fail', [
                    'note'   => $item['text'],
                    'reason' => 'contact_id is already in used'
                ]);

                continue;
            }

            $contact_ids[] = $contact_id;

            $text = $item['text'];

            $is_allow = $this->filter_description->isAllow($text);

            if (!$is_allow) {

                $this->logger->debug('Explore wall note fail', [
                    'note'   => $item['text'],
                    'reason' => 'description filter are not allow'
                ]);

                continue;
            }

            $tomita = $this->explorer->explore($text);

            $type = $tomita->getType();

            if ((int)$type === Note::TYPE_ERR) {

                $this->logger->debug('Explore wall note fail', [
                    'note'   => $item['text'],
                    'reason' => 'tomita invalid type'
                ]);

                continue;
            }

            $this->logger->debug('Explore wall note success', [
                'note' => $item['text']
            ]);

            $count++;
        }

        return $count > 3;
    }
}
